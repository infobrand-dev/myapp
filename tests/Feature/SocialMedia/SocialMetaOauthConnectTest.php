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
use Tests\TestCase;

class SocialMetaOauthConnectTest extends TestCase
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
    }

    public function test_connect_redirects_to_meta_oauth_using_platform_app_credentials(): void
    {
        [$user] = $this->makeTenantWithPlan('starter', 'tenant-social-a');

        config([
            'services.meta.app_id' => 'meta-app-123',
            'services.meta.app_secret' => 'meta-secret-123',
            'services.meta.graph_version' => 'v22.0',
        ]);

        $response = $this->actingAs($user)
            ->get('/social-media/accounts/connect/meta');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('https://www.facebook.com/v22.0/dialog/oauth', $location);
        $this->assertStringContainsString('client_id=meta-app-123', $location);
    }

    public function test_meta_oauth_callback_syncs_facebook_and_instagram_accounts_for_current_tenant(): void
    {
        [$user, $tenant] = $this->makeTenantWithPlan('starter', 'tenant-social-b');

        config([
            'services.meta.app_id' => 'meta-app-123',
            'services.meta.app_secret' => 'meta-secret-123',
            'services.meta.graph_version' => 'v22.0',
        ]);

        Http::fake([
            'https://graph.facebook.com/v22.0/oauth/access_token*' => Http::response([
                'access_token' => 'user-access-token',
            ], 200),
            'https://graph.facebook.com/v22.0/me/accounts*' => Http::response([
                'data' => [
                    [
                        'id' => 'fb-page-1',
                        'name' => 'Meetra FB Page',
                        'access_token' => 'page-access-token',
                        'instagram_business_account' => [
                            'id' => 'ig-biz-1',
                            'username' => 'meetra.ig',
                            'name' => 'Meetra IG',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($user)
            ->withSession([
                'social_media.oauth_state' => 'state-123',
                'social_media.oauth_tenant_id' => $tenant->id,
            ])
            ->get('/social-media/accounts/connect/meta/callback?state=state-123&code=oauth-code-1')
            ->assertRedirect('/social-media/accounts');

        $accounts = SocialAccount::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('platform')
            ->get();

        $this->assertCount(2, $accounts);
        $this->assertSame(['facebook', 'instagram'], $accounts->pluck('platform')->all());

        $facebook = $accounts->firstWhere('platform', 'facebook');
        $instagram = $accounts->firstWhere('platform', 'instagram');

        $this->assertSame('fb-page-1', $facebook->page_id);
        $this->assertSame('page-access-token', $facebook->access_token);
        $this->assertSame('Meetra FB Page', $facebook->name);

        $this->assertSame('ig-biz-1', $instagram->ig_business_id);
        $this->assertSame('fb-page-1', $instagram->page_id);
        $this->assertSame('page-access-token', $instagram->access_token);
        $this->assertSame('Meetra IG', $instagram->name);
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
