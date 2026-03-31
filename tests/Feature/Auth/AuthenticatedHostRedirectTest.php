<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthenticatedHostRedirectTest extends TestCase
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

    public function test_platform_owner_is_redirected_from_apex_profile_to_dash_host(): void
    {
        $user = User::factory()->create([
            'tenant_id' => 1,
            'email_verified_at' => now(),
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        $role = Role::findOrCreate('Super-admin', 'web');
        $user->assignRole($role);
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $this->actingAs($user)
            ->get('http://example.test/profile')
            ->assertRedirect('https://dash.example.test/profile');
    }

    public function test_tenant_user_is_redirected_from_apex_profile_to_workspace_host(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Workspace',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('http://example.test/profile')
            ->assertRedirect('https://acme.example.test/profile');
    }
}
