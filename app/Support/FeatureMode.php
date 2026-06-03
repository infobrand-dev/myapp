<?php

namespace App\Support;

use App\Multitenancy\QueryContextGuard;
use App\Models\User;
use App\Models\UserFeaturePreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FeatureMode
{
    public const STANDARD = 'standard';
    public const ADVANCED = 'advanced';
    public const SESSION_KEY = 'accounting_ui_mode';
    private QueryContextGuard $guard;

    public function __construct(
        QueryContextGuard $guard
    ) {
        $this->guard = $guard;
    }

    public function current(?Request $request = null, string $productLine = 'accounting', ?User $user = null): string
    {
        $request ??= request();
        $user ??= $request->user();

        $tenantId = $user?->tenant_id ?: $this->guard->requireTenant('feature mode resolution');
        $default = $this->defaultMode($productLine, $tenantId);
        $resolved = $default;

        if ($user && ($override = $this->storedOverride($user, $productLine))) {
            $resolved = $override;
        } elseif ($request->hasSession()) {
            $sessionMode = $this->normalize((string) $request->session()->get(self::SESSION_KEY, $default));
            $resolved = $sessionMode ?? $default;
        }

        if ($resolved === self::ADVANCED && !$this->canUseAdvanced($request, $productLine, $user)) {
            $resolved = self::STANDARD;
        }

        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $resolved);
        }

        return $resolved;
    }

    public function isAdvanced(?Request $request = null, string $productLine = 'accounting', ?User $user = null): bool
    {
        return $this->current($request, $productLine, $user) === self::ADVANCED;
    }

    public function canUseAdvanced(?Request $request = null, string $productLine = 'accounting', ?User $user = null): bool
    {
        $request ??= request();
        $user ??= $request->user();

        if ($user && $user->hasRole('Super-admin')) {
            return true;
        }

        $tenantId = $user?->tenant_id ?: $this->guard->requireTenant('feature mode capability check');

        if ($productLine === 'accounting') {
            return $this->planDefaultsToAdvanced($tenantId);
        }

        return false;
    }

    public function set(Request $request, string $mode, string $productLine = 'accounting', ?User $user = null): string
    {
        $user ??= $request->user();

        $tenantId = $user?->tenant_id ?: $this->guard->requireTenant('feature mode update');
        $requested = $this->normalize($mode) ?? $this->defaultMode($productLine, $tenantId);

        if ($requested === self::ADVANCED && !$this->canUseAdvanced($request, $productLine, $user)) {
            $requested = self::STANDARD;
        }

        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $requested);
        }

        if ($user && $this->preferencesTableReady()) {
            $userTenantId = $user->tenant_id ?: $tenantId;
            $default = $this->defaultMode($productLine, $userTenantId);

            if ($requested === $default) {
                UserFeaturePreference::query()
                    ->where('tenant_id', $userTenantId)
                    ->where('user_id', $user->id)
                    ->where('product_line', $productLine)
                    ->where('feature_key', (string) config('feature-modes.accounting.preference_key', 'accounting_ui_mode'))
                    ->delete();
            } else {
                UserFeaturePreference::query()->updateOrCreate(
                    [
                        'tenant_id' => $userTenantId,
                        'user_id' => $user->id,
                        'product_line' => $productLine,
                        'feature_key' => (string) config('feature-modes.accounting.preference_key', 'accounting_ui_mode'),
                    ],
                    [
                        'value' => $requested,
                    ]
                );
            }
        }

        return $requested;
    }

    public function defaultMode(string $productLine = 'accounting', ?int $tenantId = null): string
    {
        if ($productLine === 'accounting' && $this->planDefaultsToAdvanced($tenantId)) {
            return self::ADVANCED;
        }

        return self::STANDARD;
    }

    private function storedOverride(User $user, string $productLine): ?string
    {
        if (!$this->preferencesTableReady()) {
            return null;
        }

        $value = UserFeaturePreference::query()
            ->where('tenant_id', $user->tenant_id ?: $this->guard->requireTenant('feature mode stored override'))
            ->where('user_id', $user->id)
            ->where('product_line', $productLine)
            ->where('feature_key', (string) config('feature-modes.accounting.preference_key', 'accounting_ui_mode'))
            ->value('value');

        return $this->normalize((string) $value);
    }

    private function planDefaultsToAdvanced(?int $tenantId = null): bool
    {
        $featureKey = (string) config('feature-modes.accounting.advanced_feature', PlanFeature::ADVANCED_REPORTS);
        $subscription = app(TenantPlanManager::class)->currentSubscriptionFor('accounting', $tenantId);

        if ($subscription) {
            $overrides = $subscription->feature_overrides ?? [];
            if (array_key_exists($featureKey, $overrides)) {
                return (bool) $overrides[$featureKey];
            }

            return (bool) (($subscription->plan?->features ?? [])[$featureKey] ?? false);
        }

        return app(TenantPlanManager::class)->hasFeature($featureKey, $tenantId);
    }

    private function normalize(string $mode): ?string
    {
        return in_array($mode, [self::STANDARD, self::ADVANCED], true) ? $mode : null;
    }

    private function preferencesTableReady(): bool
    {
        try {
            return Schema::hasTable('user_feature_preferences');
        } catch (\Throwable) {
            return false;
        }
    }
}
