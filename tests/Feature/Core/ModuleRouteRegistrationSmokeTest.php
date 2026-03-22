<?php

namespace Tests\Feature\Core;

use App\Modules\Finance\FinanceServiceProvider;
use App\Modules\Purchases\PurchasesServiceProvider;
use App\Modules\SampleData\SampleDataServiceProvider;
use App\Modules\Shortlink\ShortlinkServiceProvider;
use App\Modules\WhatsAppApi\WhatsAppApiServiceProvider;
use App\Modules\WhatsAppWeb\WhatsAppWebServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleRouteRegistrationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_remaining_module_providers_register_expected_named_routes_at_runtime(): void
    {
        foreach ([
            FinanceServiceProvider::class,
            PurchasesServiceProvider::class,
            SampleDataServiceProvider::class,
            ShortlinkServiceProvider::class,
            WhatsAppApiServiceProvider::class,
            WhatsAppWebServiceProvider::class,
        ] as $provider) {
            $this->app->register($provider);
        }

        $routes = app('router')->getRoutes();

        $this->assertTrue($routes->hasNamedRoute('finance.transactions.index'));
        $this->assertTrue($routes->hasNamedRoute('purchases.index'));
        $this->assertTrue($routes->hasNamedRoute('sample-data.index'));
        $this->assertTrue($routes->hasNamedRoute('shortlinks.index'));
        $this->assertTrue($routes->hasNamedRoute('whatsapp-api.inbox'));
        $this->assertTrue($routes->hasNamedRoute('whatsappweb.index'));
    }
}
