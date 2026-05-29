<?php

namespace Tests\Feature\Core;

use App\Modules\Biteship\BiteshipServiceProvider;
use App\Modules\Finance\FinanceServiceProvider;
use App\Modules\Fulfillment\FulfillmentServiceProvider;
use App\Modules\Purchases\PurchasesServiceProvider;
use App\Modules\RajaOngkir\RajaOngkirServiceProvider;
use App\Modules\SampleData\SampleDataServiceProvider;
use App\Modules\Shipping\ShippingServiceProvider;
use App\Modules\Storefront\StorefrontServiceProvider;
use App\Modules\Shortlink\ShortlinkServiceProvider;
use App\Modules\Tripay\TripayServiceProvider;
use App\Modules\WhatsAppApi\WhatsAppApiServiceProvider;
use App\Modules\WhatsAppWeb\WhatsAppWebServiceProvider;
use App\Modules\Xendit\XenditServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleRouteRegistrationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_remaining_module_providers_register_expected_named_routes_at_runtime(): void
    {
        foreach ([
            BiteshipServiceProvider::class,
            FinanceServiceProvider::class,
            FulfillmentServiceProvider::class,
            PurchasesServiceProvider::class,
            RajaOngkirServiceProvider::class,
            SampleDataServiceProvider::class,
            ShippingServiceProvider::class,
            StorefrontServiceProvider::class,
            ShortlinkServiceProvider::class,
            TripayServiceProvider::class,
            WhatsAppApiServiceProvider::class,
            WhatsAppWebServiceProvider::class,
            XenditServiceProvider::class,
        ] as $provider) {
            $this->app->register($provider);
        }

        $routes = app('router')->getRoutes();

        $this->assertTrue($routes->hasNamedRoute('finance.transactions.index'));
        $this->assertTrue($routes->hasNamedRoute('fulfillment.index'));
        $this->assertTrue($routes->hasNamedRoute('purchases.index'));
        $this->assertTrue($routes->hasNamedRoute('biteship.settings.edit'));
        $this->assertTrue($routes->hasNamedRoute('rajaongkir.settings.edit'));
        $this->assertTrue($routes->hasNamedRoute('sample-data.index'));
        $this->assertTrue($routes->hasNamedRoute('shipping.index'));
        $this->assertTrue($routes->hasNamedRoute('shipping.quote'));
        $this->assertTrue($routes->hasNamedRoute('storefront.index'));
        $this->assertTrue($routes->hasNamedRoute('storefront.public.index'));
        $this->assertTrue($routes->hasNamedRoute('storefront.public.products.show'));
        $this->assertTrue($routes->hasNamedRoute('storefront.public.checkout.store'));
        $this->assertTrue($routes->hasNamedRoute('storefront.public.orders.show'));
        $this->assertTrue($routes->hasNamedRoute('tripay.settings.edit'));
        $this->assertTrue($routes->hasNamedRoute('tripay.transactions.index'));
        $this->assertTrue($routes->hasNamedRoute('tripay.webhook.notification'));
        $this->assertTrue($routes->hasNamedRoute('shortlinks.index'));
        $this->assertTrue($routes->hasNamedRoute('whatsapp-api.inbox'));
        $this->assertTrue($routes->hasNamedRoute('whatsappweb.index'));
        $this->assertTrue($routes->hasNamedRoute('xendit.settings.edit'));
        $this->assertTrue($routes->hasNamedRoute('xendit.transactions.index'));
        $this->assertTrue($routes->hasNamedRoute('xendit.webhook.notification'));
    }
}
