<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SaasAuthCriticalPathTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.mode', 'saas');
        config()->set('multitenancy.saas_domain', 'example.test');
        config()->set('multitenancy.platform_admin_subdomain', 'dash');
    }

    public function test_apex_login_redirects_to_onboarding_in_saas_mode(): void
    {
        $response = $this->get('http://example.test/login');

        $response->assertRedirect(route('onboarding.create'));
    }

    public function test_tenant_subdomain_login_screen_can_be_rendered(): void
    {
        Tenant::query()->create([
            'name' => 'Acme Workspace',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $response = $this->get('http://acme.example.test/login');

        $response->assertOk();
        $response->assertSee('Masuk ke');
        $response->assertSee('Acme Workspace');
    }

    public function test_platform_admin_host_login_screen_can_be_rendered(): void
    {
        $response = $this->get('http://dash.example.test/login');

        $response->assertOk();
        $response->assertSee('Login khusus platform admin.');
    }

    public function test_platform_admin_host_rejects_non_super_admin_login(): void
    {
        $user = User::factory()->create([
            'tenant_id' => 1,
            'email' => 'owner@example.test',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post('http://dash.example.test/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Login ini khusus untuk platform Super-admin.',
        ]);

        $this->assertGuest();
    }

    public function test_platform_admin_host_accepts_super_admin_login_for_tenant_one(): void
    {
        $user = User::factory()->create([
            'tenant_id' => 1,
            'email' => 'sa@example.test',
            'password' => bcrypt('secret123'),
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        $role = Role::findOrCreate('Super-admin', 'web');
        $user->assignRole($role);
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $response = $this->post('http://dash.example.test/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}
