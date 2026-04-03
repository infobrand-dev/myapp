<?php

namespace Tests\Feature\SocialMedia;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\SocialMedia\SocialMediaServiceProvider;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class SocialXOauthConnectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(SocialMediaServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/SocialMedia/database/migrations',
            '--force' => true,
        ]);

        $this->seed(SubscriptionPlanSeeder::class);
        $this->withoutMiddleware(PermissionMiddleware::class);
    }

    public function test_connect_redirects_to_x_oauth_using_platform_app_credentials(): void
    {
        [$user] = $this->makeTenantWithPlan('starter', 'tenant-x-a');

        config([
            'services.x_api.client_id' => 'x-client-123',
            'services.x_api.client_secret' => 'x-secret-123',
            'services.x_api.authorize_url' => 'https://x.com/i/oauth2/authorize',
        ]);

        $response = $this->actingAs($user)
            ->get('/social-media/accounts/connect/x');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('https://x.com/i/oauth2/authorize', $location);
        $this->assertStringContainsString('client_id=x-client-123', $location);
        $this->assertStringContainsString('response_type=code', $location);
    }

    public function test_x_oauth_callback_creates_or_updates_social_account_for_current_tenant(): void
    {
        [$user, $tenant] = $this->makeTenantWithPlan('starter', 'tenant-x-b');

        config([
            'services.x_api.client_id' => 'x-client-123',
            'services.x_api.client_secret' => 'x-secret-123',
            'services.x_api.token_url' => 'https://api.x.com/2/oauth2/token',
            'services.x_api.base_url' => 'https://api.x.com',
        ]);

        Http::fake([
            'https://api.x.com/2/oauth2/token' => Http::response([
                'access_token' => 'x-access-token',
                'refresh_token' => 'x-refresh-token',
                'token_type' => 'bearer',
            ], 200),
            'https://api.x.com/2/users/me*' => Http::response([
                'data' => [
                    'id' => 'x-user-123',
                    'name' => 'Meetra X',
                    'username' => 'meetrax',
                    'profile_image_url' => 'https://pbs.twimg.com/profile_images/x.jpg',
                    'verified' => true,
                ],
            ], 200),
        ]);

        $this->actingAs($user)
            ->withSession([
                'social_media.x_oauth_state' => 'x-state-123',
                'social_media.x_oauth_tenant_id' => $tenant->id,
                'social_media.x_oauth_code_verifier' => str_repeat('a', 96),
            ])
            ->get('/social-media/accounts/connect/x/callback?state=x-state-123&code=x-oauth-code-1')
            ->assertRedirect();

        $account = SocialAccount::query()
            ->where('tenant_id', $tenant->id)
            ->where('platform', 'x')
            ->first();

        $this->assertNotNull($account);
        $this->assertSame('Meetra X', $account->name);
        $this->assertSame('active', $account->status);
        $this->assertSame('x-access-token', $account->access_token);
        $this->assertSame('x-user-123', data_get($account->metadata, 'x_user_id'));
        $this->assertSame('meetrax', data_get($account->metadata, 'x_handle'));
        $this->assertSame('x_oauth', data_get($account->metadata, 'connection_source'));
        $this->assertSame('active', data_get($account->metadata, 'x_connector_status'));
    }

    private function makeTenantWithPlan(string $planCode, string $slug): array
    {
        $tenant = Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $plan = SubscriptionPlan::query()->where('code', $planCode)->firstOrFail();

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'test-' . $tenant->id,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return [$user, $tenant, $plan];
    }
}
