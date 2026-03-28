<?php

namespace App\Support;

use App\Models\Company;
use App\Models\AiUsageLog;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
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

        $sources = array_merge([
            PlanLimit::COMPANIES => ['table' => 'companies', 'model' => Company::class],
            PlanLimit::USERS => ['table' => 'users', 'model' => User::class],
        ], $this->moduleUsageSources());

        $source = $sources[$key] ?? null;

        if (!$source) {
            return 0;
        }

        return $this->countIfReady($source['table'], $source['model'], $tenantId);
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

        return $limit > 0 ? $limit : null;
    }

    private function defaultLimitMessage(string $key, ?int $tenantId = null): string
    {
        $limit = $this->limit($key, $tenantId);

        return match ($key) {
            PlanLimit::COMPANIES => "Plan tenant hanya mengizinkan maksimal {$limit} company.",
            PlanLimit::USERS => "Plan tenant hanya mengizinkan maksimal {$limit} user.",
            PlanLimit::PRODUCTS => "Plan tenant hanya mengizinkan maksimal {$limit} produk.",
            PlanLimit::CONTACTS => "Plan tenant hanya mengizinkan maksimal {$limit} contact.",
            PlanLimit::WHATSAPP_INSTANCES => "Plan tenant hanya mengizinkan maksimal {$limit} WhatsApp instance.",
            PlanLimit::EMAIL_CAMPAIGNS => "Plan tenant hanya mengizinkan maksimal {$limit} email campaign.",
            PlanLimit::AI_CREDITS_MONTHLY => "Kuota AI Credits bulanan tenant hanya {$limit} credit.",
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
