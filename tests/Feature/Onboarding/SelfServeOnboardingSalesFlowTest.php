<?php

namespace Tests\Feature\Onboarding;

use App\Mail\PlatformInvoiceIssuedMail;
use App\Mail\PlatformPaymentReceivedMail;
use App\Mail\TenantWelcomeMail;
use App\Models\PlatformInvoice;
use App\Models\PlatformPlanOrder;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SelfServeOnboardingSalesFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        config()->set('app.url', 'https://example.test');
        config()->set('multitenancy.mode', 'saas');
        config()->set('multitenancy.saas_domain', 'example.test');
        config()->set('services.midtrans.is_active', true);
        config()->set('services.midtrans.environment', 'sandbox');
        config()->set('services.midtrans.server_key', 'SB-Mid-server-test');
        config()->set('services.midtrans.client_key', 'SB-Mid-client-test');
    }

    public function test_onboarding_page_lists_public_plans_for_sale(): void
    {
        $response = $this->get(route('onboarding.create'));

        $response->assertOk();
        $response->assertSee('Omnichannel Starter');
        $response->assertSee('Omnichannel Growth');
        $response->assertSee('Omnichannel Scale');
        $response->assertSee('Paket Bulanan');
        $response->assertSee('Paket 6 Bulanan');
        $response->assertSee('Paket Tahunan');
        $response->assertSee('Hubungkan akun WhatsApp Anda sendiri');
        $response->assertSee('Lanjut ke Pembayaran');
    }

    public function test_legacy_plan_query_alias_resolves_to_current_public_revision(): void
    {
        $response = $this->get(route('onboarding.create', ['plan' => 'growth']));

        $response->assertOk();
        $response->assertViewHas('preferredPlanId', SubscriptionPlan::query()->where('code', 'growth-v2')->value('id'));
    }

    public function test_onboarding_catalog_only_shows_omnichannel_public_plans(): void
    {
        SubscriptionPlan::query()->create([
            'code' => 'crm-starter-v1',
            'name' => 'Starter',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => true,
            'is_system' => false,
            'sort_order' => 999,
            'features' => [],
            'limits' => [],
            'meta' => [
                'product_line' => 'crm',
            ],
        ]);

        $response = $this->get(route('onboarding.create'));

        $response->assertOk();
        $response->assertDontSee('CRM Starter');
        $response->assertSee('Omnichannel Growth');
    }

    public function test_self_serve_onboarding_creates_pending_workspace_invoice_and_redirects_to_midtrans(): void
    {
        Mail::fake();

        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-999',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/onboarding-checkout',
            ], 201),
        ]);

        $response = $this->post(route('onboarding.store'), [
            'subscription_plan_id' => SubscriptionPlan::query()->where('code', 'growth-v2')->value('id'),
            'company_name' => 'Acme Omnichannel',
            'slug' => 'acme',
            'name' => 'Owner Acme',
            'email' => 'owner@acme.test',
            'password' => 'Secret123!!',
            'password_confirmation' => 'Secret123!!',
        ]);

        $response->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/onboarding-checkout');

        $tenant = Tenant::query()->where('slug', 'acme')->firstOrFail();
        $order = PlatformPlanOrder::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $invoice = PlatformInvoice::query()->where('platform_plan_order_id', $order->id)->firstOrFail();

        $this->assertFalse($tenant->is_active);
        $this->assertSame('pending_payment', $tenant->meta['onboarding_status']);
        $this->assertSame('self_serve_onboarding', $order->meta['created_from']);
        $this->assertSame('midtrans', $order->payment_channel);
        $this->assertSame('pending', $invoice->status);
        $this->assertSame('owner@acme.test', $order->buyer_email);
        $this->assertNotEmpty($invoice->meta['midtrans']['redirect_url']);

        Mail::assertQueued(PlatformInvoiceIssuedMail::class);
        Mail::assertNotQueued(TenantWelcomeMail::class);
    }

    public function test_midtrans_settlement_activates_tenant_subscription_and_welcome_email_for_self_serve_onboarding(): void
    {
        Mail::fake();

        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-1000',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/onboarding-checkout',
            ], 201),
        ]);

        $this->post(route('onboarding.store'), [
            'subscription_plan_id' => SubscriptionPlan::query()->where('code', 'scale-v2')->value('id'),
            'company_name' => 'Beta Omnichannel',
            'slug' => 'beta',
            'name' => 'Owner Beta',
            'email' => 'owner@beta.test',
            'password' => 'Secret123!!',
            'password_confirmation' => 'Secret123!!',
        ])->assertRedirect();

        $invoice = PlatformInvoice::query()->latest('id')->firstOrFail();
        $tenant = $invoice->tenant;
        $orderId = $invoice->fresh()->meta['midtrans']['order_id'];
        $grossAmount = number_format((float) $invoice->amount, 2, '.', '');

        $this->postJson(route('platform.billing.midtrans.webhook'), [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'transaction_id' => 'trx-self-serve',
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', $orderId . '200' . $grossAmount . 'SB-Mid-server-test'),
        ])->assertOk();

        $tenant->refresh();
        $order = $invoice->order()->firstOrFail()->fresh();

        $this->assertTrue($tenant->is_active);
        $this->assertSame('active', $tenant->meta['onboarding_status']);
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->tenant_subscription_id);

        $subscription = TenantSubscription::query()->findOrFail($order->tenant_subscription_id);
        $this->assertSame('active', $subscription->status);

        Mail::assertQueued(PlatformInvoiceIssuedMail::class);
        Mail::assertQueued(PlatformPaymentReceivedMail::class);
        Mail::assertQueued(TenantWelcomeMail::class);
    }
}
