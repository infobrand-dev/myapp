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

    public function test_apex_root_shows_sales_landing_page(): void
    {
        $response = $this->get('http://example.test/');

        $response->assertOk();
        $response->assertSee('Satukan semua percakapan pelanggan');
        $response->assertSee('Starter');
        $response->assertSee('Growth');
        $response->assertSee('Scale');
    }

    public function test_tenant_root_redirects_guest_to_workspace_login(): void
    {
        Tenant::query()->create([
            'name' => 'Acme Workspace',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $this->get('http://acme.example.test/')
            ->assertRedirect(route('login'));
    }
}
