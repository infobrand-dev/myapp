<?php

namespace App\Modules\EmailInbox;

use App\Modules\EmailInbox\Console\Commands\FetchEmailAccounts;
use App\Support\RegistersModuleRoutes;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class EmailInboxServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PLAN_LIMIT_MODELS = [
        \App\Support\PlanLimit::EMAIL_INBOX_ACCOUNTS => [
            'table' => 'email_accounts',
            'model' => \App\Modules\EmailInbox\Models\EmailAccount::class,
        ],
    ];

    public const PERMISSIONS = [
        'email_inbox.view',
        'email_inbox.manage_accounts',
        'email_inbox.send',
        'email_inbox.sync',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => [
            'email_inbox.view',
            'email_inbox.send',
            'email_inbox.sync',
        ],
        'Customer Service' => [
            'email_inbox.view',
            'email_inbox.send',
        ],
        'Sales' => [
            'email_inbox.view',
            'email_inbox.send',
        ],
    ];

    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'emailinbox');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'emailinbox');
        $this->loadMigrationsFrom(\App\Support\ModulePath::migrationDirectory(__DIR__) ?? (__DIR__ . '/Database/Migrations'));

        $this->ensurePermissions();

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            FetchEmailAccounts::class,
        ]);

        $this->app->booted(function (): void {
            if (!config('modules.email_inbox.schedule_enabled', true)) {
                return;
            }

            $this->app->make(Schedule::class)
                ->command('email-inbox:fetch')
                ->everyFiveMinutes();
        });
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
