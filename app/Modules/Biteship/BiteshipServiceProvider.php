<?php

namespace App\Modules\Biteship;

use App\Contracts\BiteshipShippingGateway;
use App\Modules\Biteship\Adapters\BiteshipShippingGatewayAdapter;
use App\Modules\Biteship\Services\BiteshipService;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class BiteshipServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'biteship.manage_settings',
    ];

    public function register(): void
    {
        $this->app->singleton(BiteshipService::class);
        $this->app->bind(BiteshipShippingGateway::class, BiteshipShippingGatewayAdapter::class);
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'biteship');
        $this->loadMigrationsFrom(\App\Support\ModulePath::migrationDirectory(__DIR__) ?? (__DIR__ . '/Database/Migrations'));

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
