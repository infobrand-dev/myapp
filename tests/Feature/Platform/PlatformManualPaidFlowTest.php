<?php

namespace Tests\Feature\Platform;

use App\Mail\PlatformPaymentReceivedMail;
use App\Models\PlatformInvoice;
use App\Models\PlatformInvoiceItem;
use App\Models\PlatformPlanOrder;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Http\Middleware\EnsurePlatformAdminAccess;
use App\Http\Middleware\ResolveBranchContext;
use App\Http\Middleware\ResolveCompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlatformManualPaidFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_mark_order_paid_syncs_invoice_creates_payment_and_queues_emails(): void
    {
        Mail::fake();

        $platformTenant = Tenant::query()->create([
            'id' => 1,
            'name' => 'Platform',
            'slug' => 'platform',
            'is_active' => true,
        ]);

        $platformOwner = User::factory()->create([
            'tenant_id' => $platformTenant->id,
            'email_verified_at' => now(),
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);

        $superAdminRole = Role::query()->create([
            'name' => 'Super-admin',
            'guard_name' => 'web',
            'tenant_id' => 1,
        ]);

        $platformOwner->assignRole($superAdminRole);

        $tenant = Tenant::query()->create([
            'name' => 'Acme Workspace',
            'slug' => 'acme',
            'is_active' => false,
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Owner',
            'email' => 'owner@acme.test',
            'email_verified_at' => now(),
        ]);

        $plan = SubscriptionPlan::query()->where('code', 'starter')->firstOrFail();

        $order = PlatformPlanOrder::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'order_number' => 'PLAN-MANUAL-' . $tenant->id,
            'status' => 'pending',
            'amount' => 150000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'buyer_email' => 'owner@acme.test',
            'payment_channel' => 'manual',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'meta' => ['created_from' => 'test'],
        ]);

        $invoice = PlatformInvoice::query()->create([
            'tenant_id' => $tenant->id,
            'platform_plan_order_id' => $order->id,
            'subscription_plan_id' => $plan->id,
            'invoice_number' => 'INV-MANUAL-' . $tenant->id,
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

        $response = $this
            ->actingAs($platformOwner)
            ->post('https://dash.example.test/platform/orders/' . $order->id . '/mark-paid');

        $response->assertRedirect();

        $order->refresh();
        $invoice->refresh();
        $tenant->refresh();

        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertFalse($tenant->is_active);

        $this->assertDatabaseHas('platform_payments', [
            'platform_invoice_id' => $invoice->id,
            'reference' => $order->order_number,
            'payment_channel' => 'manual',
            'status' => 'paid',
        ]);

        $this->assertSame(1, TenantSubscription::query()->where('tenant_id', $tenant->id)->where('status', 'active')->count());

        Mail::assertQueued(PlatformPaymentReceivedMail::class);
        Mail::assertNotQueued(\App\Mail\TenantWelcomeMail::class);
    }
}
