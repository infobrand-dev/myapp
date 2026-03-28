<?php

namespace App\Providers;

use App\Support\ModuleManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();
        $this->registerActiveModuleProviders();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    private function registerActiveModuleProviders(): void
    {
        if ($this->shouldSkipModuleBootstrap()) {
            return;
        }

        try {
            /** @var ModuleManager $modules */
            $modules = $this->app->make(ModuleManager::class);
            foreach ($modules->activeProviders() as $providerClass) {
                $this->app->register($providerClass);
            }
        } catch (\Throwable $e) {
            // Keep core routes bootable even before modules table/setup is ready.
        }
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        // General API: 60 req/min per authenticated user or IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // Web forms (login, register, onboarding, etc.): 120 req/min per user or IP
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(120)->by(optional($request->user())->id ?: $request->ip());
        });

        // Tenant-scoped API: 300 req/min per tenant+user combination.
        // Prevents a single heavy tenant from degrading service for others.
        RateLimiter::for('tenant-api', function (Request $request) {
            $tenantId = $request->attributes->get('tenant_id')
                ?? optional($request->user())->tenant_id
                ?? 'unknown';

            $userId = optional($request->user())->id ?: $request->ip();

            return Limit::perMinute(300)->by("tenant:{$tenantId}:user:{$userId}");
        });

        // Sensitive write operations (password reset, invite, export): 10 req/10 min
        RateLimiter::for('sensitive', function (Request $request) {
            return Limit::perMinutes(10, 10)->by(optional($request->user())->id ?: $request->ip());
        });
    }

    private function shouldSkipModuleBootstrap(): bool
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
}
