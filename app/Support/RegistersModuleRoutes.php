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

            if ($this->shouldRegisterTestingFallbackRoutes($path)) {
                Route::group([], $path);
            }
        }

        $routes = $this->app['router']->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
        $this->app['url']->setRoutes($routes);
    }

    protected function shouldRegisterTestingFallbackRoutes(string $path): bool
    {
        if (!($this->app->environment('testing') && method_exists($this->app, 'isBooted') && $this->app->isBooted())) {
            return false;
        }

        $normalizedPath = str_replace('\\', '/', $path);

        foreach ([
            '/Modules/Chatbot/',
            '/Modules/Crm/',
            '/Modules/SocialMedia/',
            '/Modules/WhatsAppApi/',
        ] as $fragment) {
            if (str_contains($normalizedPath, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
