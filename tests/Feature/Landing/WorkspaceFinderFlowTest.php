<?php

namespace Tests\Feature\Landing;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceFinderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'https://example.test');
        config()->set('multitenancy.mode', 'saas');
        config()->set('multitenancy.saas_domain', 'example.test');
        config()->set('multitenancy.platform_admin_subdomain', 'dash');
    }

    public function test_workspace_finder_page_is_available_on_apex_domain(): void
    {
        $response = $this->get('https://example.test/workspace');

        $response->assertOk();
        $response->assertSee('Masuk ke Workspace');
        $response->assertSee('Lanjut ke Login');
    }

    public function test_workspace_finder_redirects_to_tenant_login(): void
    {
        Tenant::query()->create([
            'name' => 'Acme Workspace',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $response = $this->post('https://example.test/workspace', [
            'workspace' => 'acme',
        ]);

        $response->assertRedirect('https://acme.example.test/login');
    }

    public function test_workspace_finder_rejects_unknown_workspace(): void
    {
        $response = $this->from('https://example.test/workspace')->post('https://example.test/workspace', [
            'workspace' => 'missing-workspace',
        ]);

        $response->assertRedirect('https://example.test/workspace');
        $response->assertSessionHasErrors('workspace');
    }
}
