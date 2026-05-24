<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\UserInvitationService;
use App\Support\TenantRoleProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserInvitationAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'https://example.test');
        config()->set('multitenancy.mode', 'saas');
        config()->set('multitenancy.saas_domain', 'example.test');
    }

    public function test_invited_user_must_verify_email_before_dashboard_access(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Workspace',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        app(TenantRoleProvisioner::class)->ensureForTenant($tenant->id);

        $invitation = UserInvitation::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'invitee@acme.test',
            'role_name' => 'Admin',
            'token_hash' => hash('sha256', 'secret-token'),
            'expires_at' => now()->addDay(),
        ]);

        $url = app(UserInvitationService::class)->acceptUrl($tenant, $invitation, 'secret-token');

        $this->get($url)->assertOk();

        $response = $this->post('http://acme.example.test/register/invitations/' . $invitation->id, [
            'name' => 'Invitee User',
            'password' => 'Secret123!!',
            'password_confirmation' => 'Secret123!!',
            'token' => 'secret-token',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/verify-email');

        $user = User::query()->where('email', 'invitee@acme.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->email_verified_at);

        $this->get('http://acme.example.test/dashboard')
            ->assertRedirect('/verify-email');
    }
}
