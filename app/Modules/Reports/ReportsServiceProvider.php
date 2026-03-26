<?php

namespace App\Modules\Reports;

use App\Modules\Reports\Services\DashboardReportService;
use App\Modules\Reports\Services\FinanceReportService;
use App\Modules\Reports\Services\InventoryReportService;
use App\Modules\Reports\Services\PaymentReportService;
use App\Modules\Reports\Services\PosReportService;
use App\Modules\Reports\Services\PurchaseReportService;
use App\Modules\Reports\Services\SalesReportService;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ReportsServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'reports.view',
        'reports.sales',
        'reports.payments',
        'reports.inventory',
        'reports.purchases',
        'reports.finance',
        'reports.pos',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => self::PERMISSIONS,
    ];

    public function register(): void
    {
        $this->app->singleton(SalesReportService::class);
        $this->app->singleton(PaymentReportService::class);
        $this->app->singleton(InventoryReportService::class);
        $this->app->singleton(PurchaseReportService::class);
        $this->app->singleton(FinanceReportService::class);
        $this->app->singleton(PosReportService::class);
        $this->app->singleton(DashboardReportService::class);
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'reports');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'reports');

        $this->ensurePermissions();
    }

    private function ensurePermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
