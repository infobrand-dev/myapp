<?php

namespace App\Providers;

use App\Multitenancy\TenantConnectionManager;
use App\Multitenancy\TenantRuntimeTopologyResolver;
use App\Multitenancy\TenantStorageTopologyResolver;
use App\Multitenancy\TenantTopologyFingerprint;
use App\Multitenancy\TenantRegistry;
use App\Multitenancy\TenantResolver;
use App\Multitenancy\TenantTopologyValidator;
use App\Contracts\UtasWebhookNotificationSender;
use App\Models\Branch;
use App\Models\Company;
use App\Models\PlatformInvoice;
use App\Services\NativeUtasWebhookNotificationSender;
use App\Support\HookManager;
use App\Support\AccountingUiMode;
use App\Support\FeatureMode;
use App\Support\BranchContext;
use App\Support\CorePermissions;
use App\Support\CurrencySettingsResolver;
use App\Support\CompanyContext;
use App\Support\Notifications\NotificationQueryService;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use App\Support\TenantRoleProvisioner;
use App\Support\MoneyFormatter;
use App\Support\ModuleIconRegistry;
use App\Support\ModuleManager;
use App\Support\Payments\Drivers\MidtransPaymentGatewayDriver;
use App\Support\Payments\Drivers\TripayPaymentGatewayDriver;
use App\Support\Payments\Drivers\XenditPaymentGatewayDriver;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\Shipping\Drivers\BiteshipShippingProviderDriver;
use App\Support\Shipping\Drivers\RajaOngkirShippingProviderDriver;
use App\Support\Shipping\ShippingProviderManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UtasWebhookNotificationSender::class, NativeUtasWebhookNotificationSender::class);
        $this->app->singleton(ModuleManager::class, fn () => new ModuleManager());
        $this->app->singleton(ModuleIconRegistry::class, fn () => new ModuleIconRegistry());
        $this->app->singleton(HookManager::class, fn () => new HookManager());
        $this->app->singleton(CurrencySettingsResolver::class);
        $this->app->singleton(MoneyFormatter::class);
        $this->app->singleton(TenantPlanManager::class);
        $this->app->singleton(TenantRegistry::class);
        $this->app->singleton(TenantResolver::class);
        $this->app->singleton(TenantConnectionManager::class);
        $this->app->singleton(FeatureMode::class);
        $this->app->singleton(AccountingUiMode::class);
        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            return new PaymentGatewayManager([
                $app->make(MidtransPaymentGatewayDriver::class),
                $app->make(XenditPaymentGatewayDriver::class),
                $app->make(TripayPaymentGatewayDriver::class),
            ]);
        });
        $this->app->singleton(ShippingProviderManager::class, function ($app) {
            return new ShippingProviderManager([
                $app->make(BiteshipShippingProviderDriver::class),
                $app->make(RajaOngkirShippingProviderDriver::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->shouldSkipDatabaseBootstrap()) {
            return;
        }

        Queue::createPayloadUsing(function (): array {
            $resolved = TenantContext::resolvedTenant();

            if (!$resolved) {
                return [];
            }

            $tenant = $resolved->tenant->fresh(['topology.database.server', 'runtimeTopology.appServer', 'storageTopologies.storageBucket.server']);
            $runtime = app(TenantRuntimeTopologyResolver::class)->resolveForTenant($tenant);
            $storageFingerprint = app(TenantStorageTopologyResolver::class)->fingerprintForTenant($tenant);
            $topologyFingerprint = app(TenantTopologyFingerprint::class)->combined($tenant);

            return [
                'tenant_context' => [
                    'tenant_id' => $tenant->getKey(),
                    'isolation_mode' => optional($tenant->topology)->isolation_mode ?: 'tenant_id',
                    'server_key' => optional($tenant->topology)->server_key ?: 'primary',
                    'database_key' => optional($tenant->topology)->database_key ?: 'main',
                    'schema_name' => $resolved->schemaName,
                    'app_server_key' => optional($runtime)->app_server_key ?: 'primary-app',
                    'queue_cluster' => optional($runtime)->queue_cluster ?: 'default',
                    'runtime_mode' => config('multitenancy.runtime_mode', 'column'),
                    'storage_topology_fingerprint' => $storageFingerprint,
                    'topology_fingerprint' => $topologyFingerprint,
                ],
            ];
        });

        Queue::before(function ($event): void {
            $payload = $event->job->payload();
            $context = data_get($payload, 'tenant_context');

            if (!is_array($context) || empty($context['tenant_id'])) {
                return;
            }

            $tenant = app(TenantRegistry::class)->findById((int) $context['tenant_id']);
            if (!$tenant) {
                return;
            }

            app(TenantTopologyValidator::class)->assertQueuedTopologySnapshot($tenant, $context);

            $resolved = app(TenantResolver::class)->resolve($tenant);

            TenantContext::setResolvedTenant($resolved);
            app(TenantConnectionManager::class)->initialize($resolved);
        });

        Queue::after(function (): void {
            app(TenantConnectionManager::class)->purge();
            TenantContext::forget();
        });

        Queue::failing(function (): void {
            app(TenantConnectionManager::class)->purge();
            TenantContext::forget();
        });

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->bootstrapPermissionTeamId());

        Blade::if('advanced', fn () => app(FeatureMode::class)->isAdvanced());
        Blade::if('standard', fn () => !app(FeatureMode::class)->isAdvanced());

        View::composer('layouts.admin', function ($view): void {
            $tenant = TenantContext::currentTenant();
            $currentCompany = CompanyContext::currentCompany();
            $currentBranch = BranchContext::currentBranch();
            $companies = collect();
            $branches = collect();
            $pendingBilling = [
                'count' => 0,
                'latest_url' => null,
            ];
            $topbarNotifications = collect();
            $topbarNotificationCount = 0;
            $user = Auth::user();
            $userAccessManager = app(\App\Support\UserAccessManager::class);
            $allowedCompanyIds = $userAccessManager->companyIdsFor($user);
            $allowedBranchIds = $userAccessManager->branchIdsFor($user, optional($currentCompany)->id);
            $featureMode = app(FeatureMode::class);
            $accountingUiMode = $featureMode->current();
            $accountingUiModeCanUseAdvanced = $featureMode->canUseAdvanced();

            if (Auth::check() && $this->schemaHasTable('companies')) {
                $companies = Company::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->when($allowedCompanyIds, fn ($query) => $query->whereIn('id', $allowedCompanyIds->all()))
                    ->active()
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug', 'code']);
            }

            if (Auth::check() && $this->schemaHasTable('branches') && $currentCompany) {
                $branchesQuery = Branch::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', $currentCompany->id)
                    ->when($allowedBranchIds, fn ($query) => $query->whereIn('id', $allowedBranchIds->all()))
                    ->orderBy('name');

                $branches = $branchesQuery
                    ->active()
                    ->get(['id', 'company_id', 'name', 'slug', 'code']);

                if ($branches->isEmpty() && $currentBranch && (int) $currentBranch->company_id === (int) $currentCompany->id) {
                    $branches = collect([$currentBranch]);
                }
            }

            if (Auth::check() && $tenant && $this->schemaHasTable('platform_invoices')) {
                $pendingInvoices = PlatformInvoice::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->whereNotIn('status', ['paid', 'void'])
                    ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('due_at')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get(['id']);

                $pendingBilling = [
                    'count' => $pendingInvoices->count(),
                    'latest_url' => $pendingInvoices->isNotEmpty()
                        ? URL::temporarySignedRoute('platform.invoices.public', now()->addDays(30), ['invoice' => $pendingInvoices->first()->id])
                        : null,
                ];
            }

            if (
                Auth::check()
                && $this->schemaHasTable('notifications')
                && $this->schemaHasTable('notification_recipients')
                && $user
            ) {
                $notificationQuery = app(NotificationQueryService::class);
                $topbarNotifications = $notificationQuery->previewForUser($user->id, 6);
                $topbarNotificationCount = $notificationQuery->unreadCountForUser($user->id);
            }

            $view->with([
                'topbarTenant' => $tenant,
                'topbarCurrentCompany' => $currentCompany,
                'topbarCurrentBranch' => $currentBranch,
                'topbarCompanies' => $companies,
                'topbarBranches' => $branches,
                'topbarPendingBilling' => $pendingBilling,
                'topbarNotifications' => $topbarNotifications,
                'topbarNotificationCount' => $topbarNotificationCount,
                'accountingUiMode' => $accountingUiMode,
                'accountingUiModeAdvanced' => $accountingUiMode === AccountingUiMode::ADVANCED,
                'accountingUiModeCanUseAdvanced' => $accountingUiModeCanUseAdvanced,
            ]);
        });

        if (!$this->schemaHasTable('permissions')) {
            return;
        }

        if (config('permission.teams') && (
            !$this->schemaHasTable('roles')
            || !$this->schemaHasColumn('roles', config('permission.column_names.team_foreign_key', 'tenant_id'))
        )) {
            return;
        }

        if ($this->app->runningInConsole()) {
            $created = false;
            foreach (CorePermissions::PERMISSIONS as $permission) {
                $record = Permission::query()->firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                ]);

                $created = $created || $record->wasRecentlyCreated;
            }

            if ($created) {
                app(TenantRoleProvisioner::class)->ensureForAllTenants();
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        }
    }

    private function schemaHasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function schemaHasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function shouldSkipDatabaseBootstrap(): bool
    {
        if ($this->app->runningInConsole()) {
            return false;
        }

        try {
            $request = $this->app->make('request');

            return $request->is('install') || $request->is('install/*');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function bootstrapPermissionTeamId(): ?int
    {
        if (TenantContext::resolvedTenant()) {
            return TenantContext::resolvedTenant()->tenant->getKey();
        }

        if ($this->app->runningInConsole()) {
            return config('multitenancy.strict') ? null : 1;
        }

        try {
            $request = $this->app->make('request');

            $tenantId = $request->attributes->get('tenant_id');
            if ($tenantId) {
                return (int) $tenantId;
            }

            $userTenantId = optional(Auth::user())->tenant_id;
            if ($userTenantId) {
                return (int) $userTenantId;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
