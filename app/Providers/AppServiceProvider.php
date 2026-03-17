<?php

namespace App\Providers;

use App\Support\HookManager;
use App\Support\CorePermissions;
use App\Support\ModuleManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
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
        $this->app->singleton(ModuleManager::class, fn () => new ModuleManager());
        $this->app->singleton(HookManager::class, fn () => new HookManager());
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $created = false;
        foreach (CorePermissions::PERMISSIONS as $permission) {
            $record = Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);

            $created = $created || $record->wasRecentlyCreated;
        }

        if ($created) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
