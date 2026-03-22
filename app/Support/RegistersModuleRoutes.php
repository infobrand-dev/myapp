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
            Route::group([], $path);
        }

        $routes = $this->app['router']->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
        $this->app['url']->setRoutes($routes);
    }
}
