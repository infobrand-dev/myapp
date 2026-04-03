<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Company;
use App\Models\AiUsageLog;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\TenantStorageUsageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TenantPlanManager
{
    public function currentSubscription(?int $tenantId = null): ?TenantSubscription
    {
        if (!Schema::hasTable('tenant_subscriptions')) {
            return null;
        }

        $tenantId ??= TenantContext::currentId();

        $omnichannel = $this->currentSubscriptionFor('omnichannel', $tenantId);
        if ($omnichannel) {
            return $omnichannel;
        }

        return TenantSubscription::query()
            ->with('plan')
            ->where('tenant_id', $tenantId)
            ->active()
            ->latest('starts_at')
            ->latest('id')
            ->first();
    }

    public function currentSubscriptions(?int $tenantId = null)
    {
        if (!Schema::hasTable('tenant_subscriptions')) {
            return collect();
        }

        return TenantSubscription::query()
            ->with('plan')
            ->where('tenant_id', $tenantId ?? TenantContext::currentId())
            ->active()
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();
    }

    public function currentSubscriptionFor(string $productLine, ?int $tenantId = null): ?TenantSubscription
    {
        if (!Schema::hasTable('tenant_subscriptions')) {
            return null;
        }

        if (!Schema::hasColumn('tenant_subscriptions', 'product_line')) {
            return TenantSubscription::query()
                ->with('plan')
                ->where('tenant_id', $tenantId ?? TenantContext::currentId())
                ->active()
                ->latest('starts_at')
                ->latest('id')
                ->first();
        }

        return TenantSubscription::query()
            ->with('plan')
            ->where('tenant_id', $tenantId ?? TenantContext::currentId())
            ->whereIn('product_line', PlanProductLineMap::productLineCandidates($productLine))
            ->active()
            ->latest('starts_at')
            ->latest('id')
            ->first();
    }

    public function currentPlan(?int $tenantId = null): ?SubscriptionPlan
    {
        return $this->currentSubscription($tenantId)?->plan;
    }

    public function hasFeature(string $feature, ?int $tenantId = null): bool
    {
        $subscriptions = $this->effectiveSubscriptions($tenantId);

        if ($subscriptions->isEmpty()) {
            return true;
        }

        $productLine = PlanProductLineMap::featureProductLine($feature);
        if ($productLine) {
            $subscription = $this->effectiveSubscriptionFor($subscriptions, $productLine);

            if (!$subscription) {
                return false;
            }

            return $this->featureValueForSubscription($subscription, $feature, false);
        }

        foreach ($subscriptions as $subscription) {
            $overrides = $subscription->feature_overrides ?? [];

            if (array_key_exists($feature, $overrides)) {
                return (bool) $overrides[$feature];
            }
        }

        foreach ($subscriptions as $subscription) {
            if ($this->featureValueForSubscription($subscription, $feature, false)) {
                return true;
            }
        }

        return false;
    }

    public function limit(string $key, ?int $tenantId = null): ?int
    {
        $subscriptions = $this->effectiveSubscriptions($tenantId);

        if ($subscriptions->isEmpty()) {
            return null;
        }

        if (PlanProductLineMap::isSharedLimit($key)) {
            return $subscriptions
                ->map(fn (TenantSubscription $subscription) => $this->limitValueForSubscription($subscription, $key))
                ->filter(fn ($value) => $value !== null)
                ->max();
        }

        $productLine = PlanProductLineMap::limitProductLine($key);
        if ($productLine) {
            $subscription = $this->effectiveSubscriptionFor($subscriptions, $productLine);

            return $subscription ? $this->limitValueForSubscription($subscription, $key) : null;
        }

        foreach ($subscriptions as $subscription) {
            $overrides = $subscription->limit_overrides ?? [];

            if (array_key_exists($key, $overrides)) {
                return $this->normalizeLimit($overrides[$key]);
            }
        }

        return $this->limitValueForSubscription($subscriptions->first(), $key);
    }

    public function usage(string $key, ?int $tenantId = null): int
    {
        $tenantId ??= TenantContext::currentId();

        if ($key === PlanLimit::AI_CREDITS_MONTHLY) {
            return $this->aiCreditsUsage($tenantId);
        }

        if ($key === PlanLimit::TOTAL_STORAGE_BYTES) {
            return app(TenantStorageUsageService::class)->usedBytes($tenantId);
        }

        if ($key === PlanLimit::BYO_AI_REQUESTS_MONTHLY) {
            return app(\App\Services\AiUsageService::class)->byoRequestsUsedThisMonth($tenantId);
        }

        if ($key === PlanLimit::BYO_AI_TOKENS_MONTHLY) {
            return app(\App\Services\AiUsageService::class)->byoTokensUsedThisMonth($tenantId);
        }

        if ($key === PlanLimit::BYO_CHATBOT_ACCOUNTS) {
            $modelClass = \App\Modules\Chatbot\Models\ChatbotAccount::class;

            if (!class_exists($modelClass) || !Schema::hasTable('chatbot_accounts')) {
                return 0;
            }

            return (int) $modelClass::query()
                ->where('tenant_id', $tenantId)
                ->where('ai_source', 'byo')
                ->count();
        }

        if ($key === PlanLimit::EMAIL_RECIPIENTS_MONTHLY) {
            return $this->monthlyTenantCount('email_campaign_recipients', $tenantId);
        }

        if ($key === PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY) {
            return $this->monthlyTenantCount('wa_blast_recipients', $tenantId);
        }

        $sources = array_merge([
            PlanLimit::COMPANIES => ['table' => 'companies', 'model' => Company::class],
            PlanLimit::BRANCHES => ['table' => 'branches', 'model' => Branch::class],
            PlanLimit::USERS => ['table' => 'users', 'model' => User::class],
        ], $this->moduleUsageSources());

        $source = $sources[$key] ?? null;

        if (!$source) {
            return 0;
        }

        return $this->countIfReady($source['table'], $source['model'], $tenantId);
    }

    public function remaining(string $key, ?int $tenantId = null): ?int
    {
        $limit = $this->limit($key, $tenantId);

        if ($limit === null) {
            return null;
        }

        return max($limit - $this->usage($key, $tenantId), 0);
    }

    public function usageState(string $key, ?int $tenantId = null): array
    {
        $limit = $this->limit($key, $tenantId);
        $usage = $this->usage($key, $tenantId);
        $remaining = $limit === null ? null : max($limit - $usage, 0);

        if ($limit === null) {
            $status = 'ok';
        } elseif ($limit === 0) {
            $status = $usage > 0 ? 'over_limit' : 'ok';
        } elseif ($usage > $limit) {
            $status = 'over_limit';
        } elseif ($usage === $limit) {
            $status = 'at_limit';
        } elseif ($limit > 0 && $usage >= (int) ceil($limit * 0.8)) {
            $status = 'near_limit';
        } else {
            $status = 'ok';
        }

        return [
            'key' => $key,
            'limit' => $limit,
            'usage' => $usage,
            'remaining' => $remaining,
            'status' => $status,
        ];
    }

    public function limitActionAdvice(string $key, ?string $status = null, ?int $tenantId = null): ?array
    {
        $status ??= $this->usageState($key, $tenantId)['status'] ?? 'ok';

        if (!in_array($status, ['near_limit', 'at_limit', 'over_limit'], true)) {
            return null;
        }

        return match ($key) {
            PlanLimit::AI_CREDITS_MONTHLY => [
                'title' => 'Tambahkan AI Credits atau upgrade plan',
                'message' => 'Jika kuota AI Credits hampir habis atau sudah habis, tambahkan top up AI Credits atau pindahkan tenant ke plan dengan kuota AI lebih besar.',
                'tenant_cta' => 'Hubungi admin platform untuk top up AI Credits atau upgrade plan.',
                'owner_cta' => 'Gunakan Top Up AI Credits atau assign plan yang lebih tinggi.',
            ],
            PlanLimit::TOTAL_STORAGE_BYTES => [
                'title' => 'Tambah storage total workspace',
                'message' => 'Storage tenant dihitung sebagai satu kuota total lintas modul. Saat hampir penuh atau sudah penuh, upload file baru akan diblokir sampai plan dinaikkan atau file lama dibersihkan.',
                'tenant_cta' => 'Hubungi admin platform untuk upgrade storage workspace atau bersihkan file lama.',
                'owner_cta' => 'Naikkan limit total storage tenant atau bantu tenant membersihkan file lama yang tidak dipakai.',
            ],
            PlanLimit::BYO_CHATBOT_ACCOUNTS, PlanLimit::BYO_AI_REQUESTS_MONTHLY, PlanLimit::BYO_AI_TOKENS_MONTHLY => [
                'title' => 'Tingkatkan kapasitas BYO AI',
                'message' => 'Add-on BYO AI tenant ini sudah mendekati atau mencapai batas orkestrasi platform. Naikkan limit add-on atau arahkan tenant memakai Managed AI.',
                'tenant_cta' => 'Hubungi admin platform untuk menaikkan kapasitas add-on BYO AI.',
                'owner_cta' => 'Naikkan limit override BYO AI atau arahkan tenant kembali ke Managed AI.',
            ],
            PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY, PlanLimit::EMAIL_RECIPIENTS_MONTHLY => [
                'title' => 'Naikkan kuota recipient bulanan',
                'message' => 'Kuota recipient bulanan tidak bertambah otomatis. Tenant perlu upgrade plan atau penyesuaian internal dari tim platform.',
                'tenant_cta' => 'Hubungi admin platform untuk upgrade plan atau penyesuaian kuota bulanan.',
                'owner_cta' => 'Assign plan yang lebih tinggi atau lakukan penyesuaian internal untuk tenant ini.',
            ],
            PlanLimit::WHATSAPP_INSTANCES, PlanLimit::SOCIAL_ACCOUNTS, PlanLimit::LIVE_CHAT_WIDGETS, PlanLimit::CHATBOT_ACCOUNTS, PlanLimit::EMAIL_INBOX_ACCOUNTS => [
                'title' => 'Aktifkan kapasitas channel yang lebih besar',
                'message' => 'Batas channel atau connection ditambah lewat upgrade plan. Resource baru tidak bisa dibuat saat kapasitas sudah penuh.',
                'tenant_cta' => 'Hubungi admin platform untuk upgrade plan atau penyesuaian kapasitas channel.',
                'owner_cta' => 'Naikkan plan tenant atau lakukan penyesuaian internal jika memang perlu exception.',
            ],
            default => [
                'title' => 'Upgrade plan atau tambah limit',
                'message' => 'Saat kapasitas mendekati habis, tenant tetap bisa memakai data yang ada tetapi tidak bisa menambah resource baru setelah batas tercapai.',
                'tenant_cta' => 'Hubungi admin platform untuk upgrade plan atau penyesuaian kapasitas.',
                'owner_cta' => 'Assign plan yang lebih tinggi atau lakukan penyesuaian internal pada tenant ini.',
            ],
        };
    }

    public function hasCapacity(string $key, int $increment = 1, ?int $tenantId = null): bool
    {
        $limit = $this->limit($key, $tenantId);
        if ($limit === null) {
            return true;
        }

        return ($this->usage($key, $tenantId) + max(1, $increment)) <= $limit;
    }

    public function ensureWithinLimit(string $key, int $increment = 1, ?string $message = null, ?int $tenantId = null): void
    {
        if ($this->hasCapacity($key, $increment, $tenantId)) {
            return;
        }

        throw ValidationException::withMessages([
            'plan' => $message ?: $this->defaultLimitMessageFor($key, $tenantId),
        ]);
    }

    public function ensureFeature(string $feature, ?string $message = null, ?int $tenantId = null): void
    {
        if ($this->hasFeature($feature, $tenantId)) {
            return;
        }

        throw ValidationException::withMessages([
            'plan' => $message ?: 'Fitur ini tidak tersedia di plan tenant saat ini.',
        ]);
    }

    private function countIfReady(string $table, string $modelClass, int $tenantId): int
    {
        if (!class_exists($modelClass) || !Schema::hasTable($table)) {
            return 0;
        }

        return (int) $modelClass::query()
            ->where('tenant_id', $tenantId)
            ->count();
    }

    private function normalizeLimit(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $limit = (int) $value;

        return $limit < 0 ? null : $limit;
    }

    public function defaultLimitMessageFor(string $key, ?int $tenantId = null): string
    {
        $limit = $this->limit($key, $tenantId);

        return match ($key) {
            PlanLimit::COMPANIES => "Plan tenant hanya mengizinkan maksimal {$limit} company.",
            PlanLimit::BRANCHES => "Plan tenant hanya mengizinkan maksimal {$limit} branch.",
            PlanLimit::USERS => "Plan tenant hanya mengizinkan maksimal {$limit} user.",
            PlanLimit::PRODUCTS => "Plan tenant hanya mengizinkan maksimal {$limit} produk.",
            PlanLimit::CONTACTS => "Plan tenant hanya mengizinkan maksimal {$limit} contact.",
            PlanLimit::WHATSAPP_INSTANCES => "Plan tenant hanya mengizinkan maksimal {$limit} WhatsApp instance.",
            PlanLimit::SOCIAL_ACCOUNTS => "Plan tenant hanya mengizinkan maksimal {$limit} akun sosial media.",
            PlanLimit::LIVE_CHAT_WIDGETS => "Plan tenant hanya mengizinkan maksimal {$limit} live chat widget.",
            PlanLimit::CHATBOT_ACCOUNTS => "Plan tenant hanya mengizinkan maksimal {$limit} chatbot account.",
            PlanLimit::EMAIL_INBOX_ACCOUNTS => "Plan tenant hanya mengizinkan maksimal {$limit} email inbox account.",
            PlanLimit::EMAIL_CAMPAIGNS => "Plan tenant hanya mengizinkan maksimal {$limit} email campaign.",
            PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => "Kuota recipient WhatsApp blast bulanan tenant hanya {$limit} recipient.",
            PlanLimit::EMAIL_RECIPIENTS_MONTHLY => "Kuota recipient email bulanan tenant hanya {$limit} recipient.",
            PlanLimit::AI_CREDITS_MONTHLY => "Kuota AI Credits bulanan tenant hanya {$limit} credit.",
            PlanLimit::TOTAL_STORAGE_BYTES => 'Storage total workspace tenant sudah penuh. Hapus file lama atau upgrade plan untuk menambah kapasitas.',
            PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => "Plan tenant hanya mengizinkan maksimal {$limit} dokumen knowledge chatbot.",
            PlanLimit::BYO_CHATBOT_ACCOUNTS => "Add-on BYO AI tenant hanya mengizinkan maksimal {$limit} chatbot BYO.",
            PlanLimit::BYO_AI_REQUESTS_MONTHLY => "Add-on BYO AI tenant hanya mengizinkan maksimal {$limit} request AI per bulan.",
            PlanLimit::BYO_AI_TOKENS_MONTHLY => "Add-on BYO AI tenant hanya mengizinkan maksimal {$limit} token AI per bulan.",
            PlanLimit::AUTOMATION_WORKFLOWS => "Plan tenant hanya mengizinkan maksimal {$limit} workflow automation.",
            PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => "Kuota eksekusi automation bulanan tenant hanya {$limit} eksekusi.",
            default => 'Batas plan tenant sudah tercapai.',
        };
    }

    private function aiCreditsUsage(int $tenantId): int
    {
        if (!Schema::hasTable('ai_usage_logs')) {
            return 0;
        }

        return (int) AiUsageLog::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('used_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('credits_used');
    }

    private function monthlyTenantCount(string $table, int $tenantId): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    /**
     * @return array<string, array{table:string, model:class-string}>
     */
    private function moduleUsageSources(): array
    {
        $sources = [];

        foreach (app(ModuleManager::class)->all() as $module) {
            $provider = $module['provider'] ?? null;

            if (!is_string($provider) || $provider === '' || !class_exists($provider) || !defined($provider . '::PLAN_LIMIT_MODELS')) {
                continue;
            }

            foreach ((array) $provider::PLAN_LIMIT_MODELS as $key => $definition) {
                $table = is_array($definition) ? ($definition['table'] ?? null) : null;
                $model = is_array($definition) ? ($definition['model'] ?? null) : null;

                if (is_string($key) && is_string($table) && is_string($model)) {
                    $sources[$key] = [
                        'table' => $table,
                        'model' => $model,
                    ];
                }
            }
        }

        return $sources;
    }

    private function effectiveSubscriptions(?int $tenantId = null): Collection
    {
        $subscriptions = $this->currentSubscriptions($tenantId);

        if ($subscriptions->isEmpty()) {
            return collect();
        }

        if (!Schema::hasColumn('tenant_subscriptions', 'product_line')) {
            return collect([$subscriptions->first()]);
        }

        return $subscriptions
            ->groupBy(fn (TenantSubscription $subscription) => PlanProductLineMap::canonicalProductLine($subscription->productLine() ?: 'default') ?: 'default')
            ->map(fn (Collection $group) => $group->first())
            ->values();
    }

    private function effectiveSubscriptionFor(Collection $subscriptions, string $productLine): ?TenantSubscription
    {
        if (!Schema::hasColumn('tenant_subscriptions', 'product_line')) {
            return $subscriptions->first();
        }

        $candidates = PlanProductLineMap::productLineCandidates($productLine);

        return $subscriptions->first(
            fn (TenantSubscription $subscription) => in_array(($subscription->productLine() ?: 'default'), $candidates, true)
        );
    }

    private function featureValueForSubscription(TenantSubscription $subscription, string $feature, bool $default = false): bool
    {
        $overrides = $subscription->feature_overrides ?? [];

        if (array_key_exists($feature, $overrides)) {
            return (bool) $overrides[$feature];
        }

        return (bool) (($subscription->plan?->features ?? [])[$feature] ?? $default);
    }

    private function limitValueForSubscription(TenantSubscription $subscription, string $key): ?int
    {
        $overrides = $subscription->limit_overrides ?? [];

        if (array_key_exists($key, $overrides)) {
            return $this->normalizeLimit($overrides[$key]);
        }

        return $this->normalizeLimit(($subscription->plan?->limits ?? [])[$key] ?? null);
    }
}
