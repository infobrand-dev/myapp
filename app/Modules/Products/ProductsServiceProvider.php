<?php

namespace App\Modules\Products;

use App\Modules\Products\Repositories\ProductRepository;
use App\Modules\Products\Services\ProductLookupService;
use App\Modules\Products\Services\ProductService;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ProductsServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'products.view',
        'products.create',
        'products.update',
        'products.delete',
        'products.toggle-status',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => [
            'products.view',
            'products.create',
            'products.update',
            'products.toggle-status',
        ],
        'Inventory Staff' => [
            'products.view',
            'products.create',
            'products.update',
            'products.toggle-status',
        ],
    ];

    public const PLAN_LIMIT_MODELS = [
        \App\Support\PlanLimit::PRODUCTS => [
            'table' => 'products',
            'model' => \App\Modules\Products\Models\Product::class,
        ],
    ];

    public function register(): void
    {
        $this->app->singleton(ProductRepository::class);
        $this->app->singleton(ProductLookupService::class);
        $this->app->singleton(ProductService::class);
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'products');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'products');
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

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
