<?php

namespace Tests\Feature\Landing;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.mode', 'saas');
        config()->set('multitenancy.saas_domain', 'example.test');
        config()->set('multitenancy.platform_admin_subdomain', 'dash');
    }

    public function test_apex_root_shows_meetra_landing_page(): void
    {
        $response = $this->get('http://example.test/');

        $response->assertOk();
        $response->assertSee('Jalankan seluruh ekosistem bisnis Anda');
        $response->assertSee('Product Lines');
        $response->assertSee('Accounting');
        $response->assertSee('Omnichannel');
    }

    public function test_tenant_root_without_public_storefront_is_not_available(): void
    {
        Tenant::query()->create([
            'name' => 'Acme Workspace',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $this->get('http://acme.example.test/')
            ->assertNotFound();
    }
}
