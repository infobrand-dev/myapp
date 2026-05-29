<?php

namespace App\Services;

use App\Models\PlatformPlanOrder;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\BooleanQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class StaleOnboardingCleanupService
{
    public function __construct(
        private readonly TenantSlugReservationService $slugReservations,
    ) {
    }

    public function cleanup(bool $dryRun = false): array
    {
        $pendingCutoff = now()->subDays(3);
        $trialCutoff = now()->subDays(2);

        $pending = BooleanQuery::apply(
            Tenant::query()->where('created_at', '<=', $pendingCutoff),
            'is_active',
            false
        )->get()
            ->filter(fn (Tenant $tenant) => (($tenant->meta['onboarding_status'] ?? null) === 'pending_payment'))
            ->filter(fn (Tenant $tenant) => !$tenant->planOrders()->where('status', 'paid')->exists());

        $expiredTrials = Tenant::query()
            ->active()
            ->get()
            ->filter(fn (Tenant $tenant) => (($tenant->meta['onboarding_status'] ?? null) === 'trialing'))
            ->filter(function (Tenant $tenant) use ($trialCutoff): bool {
                $trial = TenantSubscription::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'trialing')
                    ->latest('id')
                    ->first();

                if (!$trial || !$trial->ends_at || $trial->ends_at->isAfter($trialCutoff)) {
                    return false;
                }

                return !$tenant->planOrders()->where('status', 'paid')->exists();
            });

        $targets = $pending->concat($expiredTrials)->unique('id')->values();
        $results = [];

        foreach ($targets as $tenant) {
            $results[] = $dryRun
                ? ['tenant_id' => $tenant->id, 'slug' => $tenant->slug, 'action' => 'would_delete']
                : $this->deleteTenant($tenant);
        }

        return [
            'count' => count($results),
            'results' => $results,
        ];
    }

    private function deleteTenant(Tenant $tenant): array
    {
        $slug = $tenant->slug;

        DB::transaction(function () use ($tenant, $slug): void {
            $this->slugReservations->reserveDetached($slug, 'cleanup_lock', now()->addDays(30), [
                'previous_tenant_id' => $tenant->id,
                'previous_status' => $tenant->meta['onboarding_status'] ?? null,
            ]);

            User::query()->where('tenant_id', $tenant->id)->delete();
            Role::query()->where('tenant_id', $tenant->id)->delete();

            DB::table(config('permission.table_names.model_has_roles'))
                ->where(config('permission.column_names.team_foreign_key', 'tenant_id'), $tenant->id)
                ->delete();

            DB::table(config('permission.table_names.model_has_permissions'))
                ->where(config('permission.column_names.team_foreign_key', 'tenant_id'), $tenant->id)
                ->delete();

            $tenant->delete();
        });

        return [
            'tenant_id' => $tenant->id,
            'slug' => $slug,
            'action' => 'deleted',
        ];
    }
}
