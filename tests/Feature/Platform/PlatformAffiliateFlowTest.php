<?php

namespace Tests\Feature\Platform;

use App\Http\Middleware\EnsurePlatformAdminAccess;
use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Http\Middleware\ResolveBranchContext;
use App\Http\Middleware\ResolveCompanyContext;
use App\Mail\PlatformAffiliateRegisteredMail;
use App\Mail\PlatformAffiliateSaleGeneratedMail;
use App\Models\PlatformAffiliate;
use App\Models\PlatformAffiliateReferral;
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
        $this->assertNotEmpty($affiliate->slug);

        Mail::assertQueued(PlatformAffiliateRegisteredMail::class);
    }

    public function test_affiliate_slug_link_captures_referral_and_notifies_when_order_paid(): void
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
            'slug' => 'mitra-a',
            'referral_code' => 'MITRA-A1',
            'status' => 'active',
            'commission_type' => 'percentage',
            'commission_rate' => 10,
            'click_count' => 0,
        ]);

        $this->get('https://example.test/aff/' . $affiliate->slug)
            ->assertRedirect(route('landing'))
            ->assertCookie('platform_affiliate_referral_code', $affiliate->referral_code);

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
            'payout_status' => 'pending',
        ]);

        Mail::assertQueued(PlatformAffiliateSaleGeneratedMail::class);
        $this->assertSame(1, $affiliate->fresh()->click_count);
    }

    public function test_affiliate_commission_is_only_paid_for_first_purchase(): void
    {
        Mail::fake();
        config()->set('services.platform_affiliate.first_purchase_only', true);

        $plan = SubscriptionPlan::query()->where('code', 'starter')->firstOrFail();

        $affiliate = PlatformAffiliate::query()->create([
            'name' => 'Mitra A',
            'email' => 'affiliate@example.test',
            'slug' => 'mitra-a',
            'referral_code' => 'MITRA-A1',
            'status' => 'active',
            'commission_type' => 'percentage',
            'commission_rate' => 20,
            'click_count' => 0,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Acme Workspace',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $firstOrder = PlatformPlanOrder::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'order_number' => 'PLAN-FIRST-001',
            'status' => 'paid',
            'amount' => 100000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'buyer_email' => 'owner@acme.test',
            'payment_channel' => 'manual',
            'paid_at' => now()->subMonth(),
        ]);

        $renewalOrder = PlatformPlanOrder::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'order_number' => 'PLAN-RENEW-001',
            'status' => 'pending',
            'amount' => 100000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'buyer_email' => 'owner@acme.test',
            'payment_channel' => 'manual',
        ]);

        $renewalReferral = PlatformAffiliateReferral::query()->create([
            'platform_affiliate_id' => $affiliate->id,
            'tenant_id' => $tenant->id,
            'platform_plan_order_id' => $renewalOrder->id,
            'referral_code' => $affiliate->referral_code,
            'buyer_email' => 'owner@acme.test',
            'status' => 'registered',
            'order_amount' => 100000,
            'order_currency' => 'IDR',
            'registered_at' => now(),
        ]);

        [$platformOwner] = $this->makePlatformOwner();

        $this->actingAs($platformOwner)
            ->post('https://dash.example.test/platform/orders/' . $renewalOrder->id . '/mark-paid')
            ->assertRedirect();

        $renewalReferral->refresh();

        $this->assertSame('converted', $renewalReferral->status);
        $this->assertSame('0.00', (string) $renewalReferral->commission_amount);
        $this->assertSame('not_eligible', $renewalReferral->payout_status);
        $this->assertFalse((bool) data_get($renewalReferral->meta, 'commission_eligible'));
        $this->assertSame('first_purchase_only', data_get($renewalReferral->meta, 'commission_reason'));
        Mail::assertNotQueued(PlatformAffiliateSaleGeneratedMail::class);
    }

    public function test_platform_owner_can_mark_affiliate_payout_as_paid(): void
    {
        [$platformOwner] = $this->makePlatformOwner();

        $affiliate = PlatformAffiliate::query()->create([
            'name' => 'Mitra A',
            'email' => 'affiliate@example.test',
            'slug' => 'mitra-a',
            'referral_code' => 'MITRA-A1',
            'status' => 'active',
            'commission_type' => 'percentage',
            'commission_rate' => 20,
            'click_count' => 0,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Acme Workspace',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $order = PlatformPlanOrder::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => SubscriptionPlan::query()->where('code', 'starter')->value('id'),
            'order_number' => 'PLAN-FIRST-001',
            'status' => 'paid',
            'amount' => 100000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'buyer_email' => 'owner@acme.test',
            'payment_channel' => 'manual',
            'paid_at' => now(),
        ]);

        $referral = PlatformAffiliateReferral::query()->create([
            'platform_affiliate_id' => $affiliate->id,
            'tenant_id' => $tenant->id,
            'platform_plan_order_id' => $order->id,
            'referral_code' => $affiliate->referral_code,
            'buyer_email' => 'owner@acme.test',
            'status' => 'converted',
            'order_amount' => 100000,
            'order_currency' => 'IDR',
            'commission_amount' => 20000,
            'payout_status' => 'pending',
            'registered_at' => now()->subDay(),
            'converted_at' => now()->subDay(),
        ]);

        $this->actingAs($platformOwner)
            ->post('https://dash.example.test/platform/affiliates/' . $affiliate->id . '/referrals/' . $referral->id . '/payout', [
                'payout_status' => 'paid',
                'payout_reference' => 'PAYOUT-001',
                'payout_notes' => 'Transfer bank',
            ])
            ->assertRedirect();

        $referral->refresh();

        $this->assertSame('paid', $referral->payout_status);
        $this->assertNotNull($referral->approved_at);
        $this->assertNotNull($referral->paid_at);
        $this->assertSame('PAYOUT-001', $referral->payout_reference);
    }

    public function test_platform_owner_can_open_affiliate_payout_queue(): void
    {
        [$platformOwner] = $this->makePlatformOwner();

        $response = $this->actingAs($platformOwner)
            ->get('https://dash.example.test/platform/affiliate-payouts');

        $response->assertOk();
        $response->assertSee('Affiliate Payouts');
    }

    public function test_public_affiliate_program_page_uses_configured_policy(): void
    {
        config()->set('services.platform_affiliate.cookie_days', 30);
        config()->set('services.platform_affiliate.first_purchase_only', true);

        $response = $this->get('https://example.test/affiliate-program');

        $response->assertOk();
        $response->assertSee('30 hari');
        $response->assertSee('pembelian pertama', false);
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
