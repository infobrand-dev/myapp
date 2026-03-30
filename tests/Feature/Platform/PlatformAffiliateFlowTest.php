<?php

namespace Tests\Feature\Platform;

use App\Http\Middleware\EnsurePlatformAdminAccess;
use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Http\Middleware\ResolveBranchContext;
use App\Http\Middleware\ResolveCompanyContext;
use App\Mail\PlatformAffiliateRegisteredMail;
use App\Mail\PlatformAffiliateSaleGeneratedMail;
use App\Models\PlatformAffiliate;
use App\Models\PlatformPlanOrder;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlatformAffiliateFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        config()->set('app.url', 'https://example.test');
        config()->set('multitenancy.mode', 'saas');
        config()->set('multitenancy.saas_domain', 'example.test');
        config()->set('multitenancy.platform_admin_subdomain', 'dash');

        $this->withoutMiddleware([
            EnsureEmailIsVerified::class,
            EnsureTwoFactorAuthenticated::class,
            EnsurePlatformAdminAccess::class,
            ResolveCompanyContext::class,
            ResolveBranchContext::class,
        ]);
    }

    public function test_platform_owner_can_create_affiliate_and_queue_registered_mail(): void
    {
        Mail::fake();

        [$platformOwner] = $this->makePlatformOwner();

        $response = $this
            ->actingAs($platformOwner)
            ->post('https://dash.example.test/platform/affiliates', [
                'name' => 'Mitra A',
                'email' => 'affiliate@example.test',
                'phone' => '08123',
                'commission_type' => 'percentage',
                'commission_rate' => 12,
                'status' => 'active',
                'notes' => 'Top performer',
            ]);

        $response->assertRedirect(route('platform.affiliates.index'));

        $affiliate = PlatformAffiliate::query()->where('email', 'affiliate@example.test')->first();

        $this->assertNotNull($affiliate);
        $this->assertSame('active', $affiliate->status);
        $this->assertNotEmpty($affiliate->referral_code);

        Mail::assertQueued(PlatformAffiliateRegisteredMail::class);
    }

    public function test_affiliate_referral_is_recorded_on_onboarding_and_notified_when_order_paid(): void
    {
        Mail::fake();
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/checkout-token',
            ], 201),
        ]);

        config()->set('services.midtrans.is_active', true);
        config()->set('services.midtrans.environment', 'sandbox');
        config()->set('services.midtrans.server_key', 'SB-Mid-server-test');
        config()->set('services.midtrans.client_key', 'SB-Mid-client-test');

        $plan = SubscriptionPlan::query()->where('code', 'starter')->firstOrFail();

        $affiliate = PlatformAffiliate::query()->create([
            'name' => 'Mitra A',
            'email' => 'affiliate@example.test',
            'referral_code' => 'MITRA-A1',
            'status' => 'active',
            'commission_type' => 'percentage',
            'commission_rate' => 10,
        ]);

        $this->get('https://example.test/?ref=' . $affiliate->referral_code)->assertOk();

        $this->post('https://example.test/onboarding', [
            'subscription_plan_id' => $plan->id,
            'company_name' => 'Acme Workspace',
            'slug' => 'acme',
            'name' => 'Owner Acme',
            'email' => 'owner@acme.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/checkout-token');

        $order = PlatformPlanOrder::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('platform_affiliate_referrals', [
            'platform_affiliate_id' => $affiliate->id,
            'platform_plan_order_id' => $order->id,
            'status' => 'registered',
        ]);

        [$platformOwner] = $this->makePlatformOwner();

        $this->actingAs($platformOwner)
            ->post('https://dash.example.test/platform/orders/' . $order->id . '/mark-paid')
            ->assertRedirect();

        $this->assertDatabaseHas('platform_affiliate_referrals', [
            'platform_affiliate_id' => $affiliate->id,
            'platform_plan_order_id' => $order->id,
            'status' => 'converted',
        ]);

        Mail::assertQueued(PlatformAffiliateSaleGeneratedMail::class);
    }

    private function makePlatformOwner(): array
    {
        $platformTenant = Tenant::query()->firstOrCreate([
            'id' => 1,
        ], [
            'name' => 'Platform',
            'slug' => 'platform',
            'is_active' => true,
        ]);

        $platformOwner = User::factory()->create([
            'tenant_id' => $platformTenant->id,
            'email_verified_at' => now(),
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);

        $role = Role::query()->firstOrCreate([
            'name' => 'Super-admin',
            'guard_name' => 'web',
            'tenant_id' => 1,
        ]);

        $platformOwner->assignRole($role);

        return [$platformOwner, $platformTenant];
    }
}
