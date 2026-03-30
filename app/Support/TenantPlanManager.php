<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Company;
use App\Models\AiUsageLog;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
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

        return TenantSubscription::query()
            ->with('plan')
            ->where('tenant_id', $tenantId ?? TenantContext::currentId())
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
        $subscription = $this->currentSubscription($tenantId);
        if (!$subscription) {
            return true;
        }

        $overrides = $subscription->feature_overrides ?? [];
        if (array_key_exists($feature, $overrides)) {
            return (bool) $overrides[$feature];
        }

        return (bool) (($subscription->plan?->features ?? [])[$feature] ?? false);
    }

    public function limit(string $key, ?int $tenantId = null): ?int
    {
        $subscription = $this->currentSubscription($tenantId);
        if (!$subscription) {
            return null;
        }

        $overrides = $subscription->limit_overrides ?? [];
        if (array_key_exists($key, $overrides)) {
            return $this->normalizeLimit($overrides[$key]);
        }

        return $this->normalizeLimit(($subscription->plan?->limits ?? [])[$key] ?? null);
    }

    public function usage(string $key, ?int $tenantId = null): int
    {
        $tenantId ??= TenantContext::currentId();

        if ($key === PlanLimit::AI_CREDITS_MONTHLY) {
            return $this->aiCreditsUsage($tenantId);
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
            'plan' => $message ?: $this->defaultLimitMessage($key, $tenantId),
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

    private function defaultLimitMessage(string $key, ?int $tenantId = null): string
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
            PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => "Plan tenant hanya mengizinkan maksimal {$limit} dokumen knowledge chatbot.",
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
}
