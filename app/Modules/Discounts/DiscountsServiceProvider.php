<?php

namespace App\Modules\Discounts;

use App\Modules\Discounts\Actions\EvaluateDiscountsAction;
use App\Modules\Discounts\Actions\RecordDiscountUsageAction;
use App\Modules\Discounts\Actions\UpsertDiscountAction;
use App\Modules\Discounts\Repositories\DiscountRepository;
use App\Modules\Discounts\Services\DiscountEngine;
use App\Modules\Discounts\Services\DiscountReferenceService;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class DiscountsServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'discounts.view',
        'discounts.create',
        'discounts.update',
        'discounts.activate',
        'discounts.archive',
        'discounts.manage-vouchers',
        'discounts.view-usage',
        'discounts.evaluate',
        'discounts.apply-manual',
        'discounts.override',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => [
            'discounts.view',
            'discounts.create',
            'discounts.update',
            'discounts.activate',
            'discounts.manage-vouchers',
            'discounts.view-usage',
            'discounts.evaluate',
            'discounts.apply-manual',
        ],
    ];

    public function register(): void
    {
        $this->app->singleton(DiscountRepository::class);
        $this->app->singleton(DiscountReferenceService::class);
        $this->app->singleton(DiscountEngine::class);
        $this->app->singleton(UpsertDiscountAction::class);
        $this->app->singleton(EvaluateDiscountsAction::class);
        $this->app->singleton(RecordDiscountUsageAction::class);
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'discounts');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

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
