<?php

namespace App\Modules\Contacts;

use App\Support\RegistersModuleRoutes;
use Illuminate\Support\ServiceProvider;

class ContactsServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'contacts');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'contacts');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
