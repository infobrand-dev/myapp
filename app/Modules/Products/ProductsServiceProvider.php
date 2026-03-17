<?php

namespace App\Modules\Products;

use App\Modules\Products\Repositories\ProductRepository;
use App\Modules\Products\Services\ProductLookupService;
use App\Modules\Products\Services\ProductService;
use Illuminate\Support\ServiceProvider;

class ProductsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProductRepository::class);
        $this->app->singleton(ProductLookupService::class);
        $this->app->singleton(ProductService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'products');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
