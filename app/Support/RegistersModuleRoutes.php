<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

trait RegistersModuleRoutes
{
    protected function registerModuleRoutes(array $paths): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        foreach ($paths as $path) {
            if (config('multitenancy.mode') !== 'saas') {
                Route::group([], $path);
                continue;
            }

            foreach (SaasHost::candidateSubdomainPatterns() as $domain) {
                Route::domain($domain)->group($path);
            }

            if ($this->shouldRegisterGenericHostRoutes($path)) {
                Route::group([], $path);
            }
        }

        $routes = $this->app['router']->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
        $this->app['url']->setRoutes($routes);
    }

    protected function shouldRegisterGenericHostRoutes(string $path): bool
    {
        if (config('multitenancy.mode') !== 'saas') {
            return false;
        }

        return false;
    }
}
