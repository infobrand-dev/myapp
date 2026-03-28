<?php

namespace Tests\Feature\Platform;

use App\Models\PlatformInvoice;
use App\Models\PlatformInvoiceItem;
use App\Models\PlatformPlanOrder;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PlatformBillingHappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
    }

    public function test_public_checkout_creates_midtrans_transaction_and_marks_invoice_pending(): void
    {
        $invoice = $this->makePlatformInvoice();

        config()->set('services.midtrans.is_active', true);
        config()->set('services.midtrans.environment', 'sandbox');
        config()->set('services.midtrans.server_key', 'SB-Mid-server-test');
        config()->set('services.midtrans.client_key', 'SB-Mid-client-test');
        config()->set('services.midtrans.enabled_payments', ['bca_va', 'gopay']);

        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/checkout-token',
            ], 201),
        ]);

        $signedCheckoutUrl = URL::temporarySignedRoute(
            'platform.invoices.public.midtrans.checkout',
            now()->addMinutes(30),
            ['invoice' => $invoice->id]
        );

        $response = $this->post($signedCheckoutUrl);

        $response->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/checkout-token');

        $invoice->refresh();

        $this->assertSame('pending', $invoice->status);
        $this->assertStringStartsWith('PLATINV-' . $invoice->id . '-', $invoice->meta['midtrans']['order_id']);
        $this->assertSame('snap-token-123', $invoice->meta['midtrans']['snap_token']);
        $this->assertSame('https://app.sandbox.midtrans.com/snap/v2/vtweb/checkout-token', $invoice->meta['midtrans']['redirect_url']);
        $this->assertSame('pending', $invoice->meta['midtrans']['transaction_status']);

        Http::assertSent(function ($request) use ($invoice) {
            $items = $request->data()['item_details'] ?? [];

            return $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
                && count($items) === 1
                && $items[0]['id'] === 'starter'
                && $items[0]['quantity'] === 1
                && $items[0]['price'] === 150000
                && $items[0]['name'] === 'Starter';
        });
    }

    public function test_midtrans_settlement_webhook_marks_invoice_paid_creates_payment_and_activates_subscription(): void
    {
        Mail::fake();

        $invoice = $this->makePlatformInvoice();

        config()->set('services.midtrans.is_active', true);
        config()->set('services.midtrans.environment', 'sandbox');
        config()->set('services.midtrans.server_key', 'SB-Mid-server-test');
        config()->set('services.midtrans.client_key', 'SB-Mid-client-test');

        $orderId = 'PLATINV-' . $invoice->id . '-20260328120000';
        $invoice->forceFill([
            'status' => 'pending',
            'meta' => [
                'midtrans' => [
                    'order_id' => $orderId,
                    'transaction_status' => 'pending',
                ],
            ],
        ])->save();

        $grossAmount = number_format((float) $invoice->amount, 2, '.', '');
        $payload = [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'transaction_id' => 'trx-123',
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', $orderId . '200' . $grossAmount . 'SB-Mid-server-test'),
        ];

        $response = $this->postJson(route('platform.billing.midtrans.webhook'), $payload);

        $response->assertOk()->assertJson(['message' => 'OK']);

        $invoice->refresh();
        $order = $invoice->order->fresh();

        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertSame('settlement', $invoice->meta['midtrans']['transaction_status']);
        $this->assertSame('trx-123', $invoice->meta['midtrans']['transaction_id']);

        $this->assertDatabaseHas('platform_payments', [
            'platform_invoice_id' => $invoice->id,
            'payment_channel' => 'midtrans',
            'reference' => $orderId,
            'status' => 'paid',
        ]);

        $this->assertSame('paid', $order->status);
        $this->assertSame('midtrans', $order->payment_channel);
        $this->assertNotNull($order->tenant_subscription_id);

        $subscription = TenantSubscription::query()->findOrFail($order->tenant_subscription_id);
        $this->assertSame('active', $subscription->status);
        $this->assertSame('midtrans', $subscription->billing_provider);
        $this->assertSame($orderId, $subscription->billing_reference);

        Mail::assertQueued(\App\Mail\PlatformPaymentReceivedMail::class);
    }

    public function test_duplicate_settlement_webhook_does_not_create_duplicate_platform_payment(): void
    {
        Mail::fake();

        $invoice = $this->makePlatformInvoice();

        config()->set('services.midtrans.is_active', true);
        config()->set('services.midtrans.environment', 'sandbox');
        config()->set('services.midtrans.server_key', 'SB-Mid-server-test');
        config()->set('services.midtrans.client_key', 'SB-Mid-client-test');

        $orderId = 'PLATINV-' . $invoice->id . '-20260328120100';
        $invoice->forceFill([
            'status' => 'pending',
            'meta' => [
                'midtrans' => [
                    'order_id' => $orderId,
                    'transaction_status' => 'pending',
                ],
            ],
        ])->save();

        $grossAmount = number_format((float) $invoice->amount, 2, '.', '');
        $payload = [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'transaction_id' => 'trx-456',
            'payment_type' => 'bca_va',
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', $orderId . '200' . $grossAmount . 'SB-Mid-server-test'),
        ];

        $this->postJson(route('platform.billing.midtrans.webhook'), $payload)->assertOk();
        $this->postJson(route('platform.billing.midtrans.webhook'), $payload)->assertOk();

        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(1, PlatformPlanOrder::query()->whereKey($invoice->platform_plan_order_id)->where('status', 'paid')->count());
    }

    private function makePlatformInvoice(): PlatformInvoice
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Workspace',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'owner@acme.test',
        ]);

        $plan = SubscriptionPlan::query()->where('code', 'starter')->firstOrFail();

        $order = PlatformPlanOrder::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'order_number' => 'PLAN-TEST-' . $tenant->id,
            'status' => 'pending',
            'amount' => 150000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'buyer_email' => 'owner@acme.test',
            'payment_channel' => 'midtrans',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'meta' => ['created_from' => 'test'],
        ]);

        $invoice = PlatformInvoice::query()->create([
            'tenant_id' => $tenant->id,
            'platform_plan_order_id' => $order->id,
            'subscription_plan_id' => $plan->id,
            'invoice_number' => 'INV-TEST-' . $tenant->id,
            'status' => 'issued',
            'amount' => 150000,
            'currency' => 'IDR',
            'issued_at' => now(),
            'due_at' => now()->addWeek(),
            'meta' => [],
        ]);

        PlatformInvoiceItem::query()->create([
            'platform_invoice_id' => $invoice->id,
            'item_type' => 'plan',
            'item_code' => $plan->code,
            'name' => $plan->name,
            'description' => 'Starter plan monthly subscription',
            'quantity' => 1,
            'unit_price' => 150000,
            'total_price' => 150000,
            'meta' => [],
        ]);

        return $invoice;
    }
}
