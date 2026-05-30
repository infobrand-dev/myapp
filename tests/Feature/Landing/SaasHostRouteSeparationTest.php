<?php

namespace Tests\Feature\Landing;

use App\Models\Tenant;
use App\Modules\Products\ProductsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasHostRouteSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'https://myapp.test');
        config()->set('multitenancy.mode', 'saas');
        config()->set('multitenancy.saas_domain', 'meetra.id');
        config()->set('multitenancy.platform_admin_subdomain', 'dash');

        $this->app->register(ProductsServiceProvider::class);
    }

    public function test_apex_products_route_renders_public_landing_page(): void
    {
        $this->get('https://myapp.test/products')
            ->assertOk()
            ->assertSee('Solusi Meetra untuk kebutuhan bisnis yang berbeda.');
    }

    public function test_tenant_subdomain_products_route_does_not_render_public_landing_page(): void
    {
        Tenant::query()->create([
            'name' => 'Acme Workspace',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $this->get('https://acme.myapp.test/products')
            ->assertRedirect('https://acme.myapp.test');
    }
}
