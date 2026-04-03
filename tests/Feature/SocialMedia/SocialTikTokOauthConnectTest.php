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

class SocialTikTokOauthConnectTest extends TestCase
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

    public function test_connect_redirects_to_tiktok_oauth_using_platform_app_credentials(): void
    {
        [$user] = $this->makeTenantWithPlan('starter', 'tenant-tiktok-a');

        config([
            'services.tiktok_api.client_key' => 'tiktok-client-key',
            'services.tiktok_api.client_secret' => 'tiktok-client-secret',
            'services.tiktok_api.authorize_url' => 'https://www.tiktok.com/v2/auth/authorize/',
        ]);

        $response = $this->actingAs($user)
            ->get('/social-media/accounts/connect/tiktok');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('https://www.tiktok.com/v2/auth/authorize/', $location);
        $this->assertStringContainsString('client_key=tiktok-client-key', $location);
    }

    public function test_tiktok_oauth_callback_creates_social_account_for_current_tenant(): void
    {
        [$user, $tenant] = $this->makeTenantWithPlan('starter', 'tenant-tiktok-b');

        config([
            'services.tiktok_api.client_key' => 'tiktok-client-key',
            'services.tiktok_api.client_secret' => 'tiktok-client-secret',
            'services.tiktok_api.token_url' => 'https://open.tiktokapis.com/v2/oauth/token/',
            'services.tiktok_api.base_url' => 'https://open.tiktokapis.com',
        ]);

        Http::fake([
            'https://open.tiktokapis.com/v2/oauth/token/' => Http::response([
                'access_token' => 'tt-access-token',
                'refresh_token' => 'tt-refresh-token',
            ], 200),
            'https://open.tiktokapis.com/v2/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'open_id' => 'open-id-123',
                        'union_id' => 'union-id-123',
                        'display_name' => 'Meetra TikTok',
                        'username' => 'meetratiktok',
                        'avatar_url' => 'https://p16-sign.tiktokcdn.com/avatar.jpg',
                        'profile_deep_link' => 'https://www.tiktok.com/@meetratiktok',
                        'follower_count' => 1200,
                        'following_count' => 45,
                        'likes_count' => 9800,
                        'video_count' => 17,
                    ],
                ],
            ], 200),
            'https://open.tiktokapis.com/v2/video/list/' => Http::response([
                'data' => [
                    'videos' => [
                        [
                            'id' => 'video-1',
                            'title' => 'Video Demo',
                            'share_url' => 'https://www.tiktok.com/@meetratiktok/video/1',
                            'view_count' => 500,
                            'like_count' => 50,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($user)
            ->withSession([
                'social_media.tiktok_oauth_state' => 'tt-state-123',
                'social_media.tiktok_oauth_tenant_id' => $tenant->id,
            ])
            ->get('/social-media/accounts/connect/tiktok/callback?state=tt-state-123&code=tt-oauth-code-1')
            ->assertRedirect();

        $account = SocialAccount::query()
            ->where('tenant_id', $tenant->id)
            ->where('platform', 'tiktok')
            ->first();

        $this->assertNotNull($account);
        $this->assertSame('Meetra TikTok', $account->name);
        $this->assertSame('active', $account->status);
        $this->assertSame('tt-access-token', $account->access_token);
        $this->assertSame('open-id-123', data_get($account->metadata, 'tiktok_open_id'));
        $this->assertSame('meetratiktok', data_get($account->metadata, 'tiktok_username'));
        $this->assertSame(1200, data_get($account->metadata, 'tiktok_stats.followers'));
        $this->assertCount(1, (array) data_get($account->metadata, 'tiktok_videos', []));
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
