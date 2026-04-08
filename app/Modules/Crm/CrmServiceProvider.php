<?php

namespace App\Modules\Crm;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Crm\Models\CrmLead;
use App\Support\BooleanQuery;
use App\Support\HookManager;
use App\Support\PlanFeature;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CrmServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'crm.view',
        'crm.create',
        'crm.update',
        'crm.delete',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => self::PERMISSIONS,
        'Customer Service' => [
            'crm.view',
            'crm.create',
            'crm.update',
        ],
        'Sales' => [
            'crm.view',
            'crm.create',
            'crm.update',
        ],
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'crm');
        $this->loadMigrationsFrom(\App\Support\ModulePath::migrationDirectory(__DIR__) ?? (__DIR__ . '/Database/Migrations'));
        $this->ensurePermissions();
        $this->registerContactHooks();
        $this->registerDashboardHooks();
    }

    private function ensurePermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $created = false;

        foreach (self::PERMISSIONS as $permission) {
            $record = Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);

            $created = $created || $record->wasRecentlyCreated;
        }

        if ($created) {
            app(\App\Support\TenantRoleProvisioner::class)->ensureForAllTenants();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function registerDashboardHooks(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $hooks->register('dashboard.overview.cards', 'crm.dashboard.card', function (): string {
            $user = auth()->user();

            if (!$user || !Schema::hasTable('crm_leads')) {
                return '';
            }

            if (!app(\App\Support\TenantPlanManager::class)->hasFeature(PlanFeature::CRM, \App\Support\TenantContext::currentId())) {
                return '';
            }

            $baseQuery = CrmLead::query()
                ->where('tenant_id', \App\Support\TenantContext::currentId());
            BooleanQuery::apply($baseQuery, 'is_archived', false);

            if (!$user->hasAnyRole(['Super-admin', 'Admin'])) {
                $baseQuery->where('owner_user_id', $user->id);
            }

            $metrics = [
                'active'          => (clone $baseQuery)->whereNotIn('stage', ['won', 'lost'])->count(),
                'follow_up_due'   => (clone $baseQuery)->whereNotIn('stage', ['won', 'lost'])->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()->endOfDay())->count(),
                'won_this_month'  => (clone $baseQuery)->where('stage', 'won')->whereMonth('won_at', now()->month)->whereYear('won_at', now()->year)->count(),
                'audience'        => $user->hasAnyRole(['Super-admin', 'Admin']) ? 'global' : 'personal',
            ];

            return view('crm::dashboard.card', compact('metrics'))->render();
        });
    }

    private function registerContactHooks(): void
    {
        if (!class_exists(Contact::class)) {
            return;
        }

        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $renderAction = function (array $context): string {
            $user = auth()->user();

            if (!Schema::hasTable('crm_leads')
                || !$user
                || !app(\App\Support\TenantPlanManager::class)->hasFeature(PlanFeature::CRM, \App\Support\TenantContext::currentId())
                || !$user->can('crm.view')) {
                return '';
            }

            /** @var Contact|null $contact */
            $contact = $context['contact'] ?? null;
            if (!$contact) {
                return '';
            }

            $lead = CrmLead::query()
                ->where('tenant_id', $contact->tenant_id)
                ->where('contact_id', $contact->id)
                ->latest('id')
                ->first();

            return view('crm::hooks.contact-action', compact('contact', 'lead'))->render();
        };

        $hooks->register('contacts.index.row_actions', 'crm.contact_action', $renderAction);
        $hooks->register('contacts.show.header_actions', 'crm.contact_action', $renderAction);
    }
}
