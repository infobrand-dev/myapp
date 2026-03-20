<?php

namespace App\Providers;

use App\Support\HookManager;
use App\Support\CorePermissions;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use App\Modules\LiveChat\Support\LiveChatRealtimeState;
use App\Support\ModuleIconRegistry;
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
        $this->app->singleton(ModuleIconRegistry::class, fn () => new ModuleIconRegistry());
        $this->app->singleton(HookManager::class, fn () => new HookManager());
        $this->app->singleton(LiveChatRealtimeState::class, fn () => new LiveChatRealtimeState());
        $this->app->singleton(TenantPlanManager::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(TenantContext::currentId());

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
