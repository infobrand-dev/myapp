<?php

namespace Tests\Feature\Payments;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\PaymentsServiceProvider;
use App\Modules\Products\Models\Product;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\Actions\FinalizeSaleAction;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\SalesServiceProvider;
use App\Support\FeatureMode;
use App\Support\PlanFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BootstrapsModuleContext;
use Tests\TestCase;

class PaymentStandardModeTest extends TestCase
{
    use BootstrapsModuleContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('PaymentStandardModeTest harus dijalankan di PostgreSQL atau database non-SQLite yang setara dengan runtime aplikasi.');
        }

        $this->registerModuleProviders([
            PaymentsServiceProvider::class,
            SalesServiceProvider::class,
        ]);

        $this->migrateModulePaths([
            'app/Modules/Contacts/database/migrations',
            'app/Modules/Products/database/migrations',
            'app/Modules/Payments/database/migrations',
            'app/Modules/PointOfSale/database/migrations',
            'app/Modules/Sales/database/migrations',
        ]);

        $this->bootstrapDefaultOperationalContext(companyAttributes: [
            'meta' => [],
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_standard_mode_can_create_payment_with_single_allocation(): void
    {
        $user = $this->userWithPermissions(['sales.create', 'sales.finalize', 'payments.create']);
        $company = $this->attachCompanyAccess($user);
        $this->activateStarterPlan();
        $sale = $this->finalizedSale($user);

        $response = $this->actingAs($user)
            ->withSession([
                'company_id' => $company->id,
                'company_slug' => $company->slug,
                FeatureMode::SESSION_KEY => FeatureMode::STANDARD,
            ])
            ->post(route('payments.store'), [
                'payment_method_id' => PaymentMethod::query()->where('code', 'cash')->value('id'),
                'amount' => 10000,
                'allocations' => [[
                    'payable_type' => 'sale',
                    'payable_id' => $sale->id,
                    'amount' => 10000,
                ]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_standard_mode_rejects_multi_allocation_payment_creation(): void
    {
        $user = $this->userWithPermissions(['sales.create', 'sales.finalize', 'payments.create']);
        $company = $this->attachCompanyAccess($user);
        $this->activateStarterPlan();
        $sale = $this->finalizedSale($user);

        $response = $this->actingAs($user)
            ->withSession([
                'company_id' => $company->id,
                'company_slug' => $company->slug,
                FeatureMode::SESSION_KEY => FeatureMode::STANDARD,
            ])
            ->post(route('payments.store'), [
                'payment_method_id' => PaymentMethod::query()->where('code', 'cash')->value('id'),
                'amount' => 10000,
                'allocations' => [
                    [
                        'payable_type' => 'sale',
                        'payable_id' => $sale->id,
                        'amount' => 5000,
                    ],
                    [
                        'payable_type' => 'sale',
                        'payable_id' => $sale->id,
                        'amount' => 5000,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('allocations');
    }

    public function test_standard_update_preserves_hidden_advanced_payment_fields(): void
    {
        $user = $this->userWithPermissions(['sales.create', 'sales.finalize', 'payments.create', 'payments.view']);
        $company = $this->attachCompanyAccess($user);
        $this->activateStarterPlan();
        $sale = $this->finalizedSale($user);

        $payment = app(CreatePaymentAction::class)->execute([
            'payment_method_id' => PaymentMethod::query()->where('code', 'cash')->value('id'),
            'amount' => 10000,
            'source' => Payment::SOURCE_BACKOFFICE,
            'reference_number' => 'REF-STD-001',
            'external_reference' => 'EXT-STD-001',
            'channel' => 'bank_transfer',
            'received_by' => $user->id,
            'reconciliation_status' => Payment::RECONCILIATION_IN_REVIEW,
            'allocations' => [[
                'payable_type' => 'sale',
                'payable_id' => $sale->id,
                'amount' => 10000,
            ]],
        ], $user);

        $response = $this->actingAs($user)
            ->withSession([
                'company_id' => $company->id,
                'company_slug' => $company->slug,
                FeatureMode::SESSION_KEY => FeatureMode::STANDARD,
            ])
            ->put(route('payments.update', $payment), [
                'payment_method_id' => $payment->payment_method_id,
                'amount' => 10000,
                'notes' => 'Update standard mode',
                'allocations' => [[
                    'payable_type' => 'sale',
                    'payable_id' => $sale->id,
                    'amount' => 10000,
                ]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'reference_number' => 'REF-STD-001',
            'external_reference' => 'EXT-STD-001',
            'channel' => 'bank_transfer',
            'reconciliation_status' => Payment::RECONCILIATION_IN_REVIEW,
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['tenant_id' => 1]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function attachCompanyAccess(User $user): Company
    {
        $company = Company::query()->findOrFail(1);

        \DB::table('user_companies')->insert([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $company;
    }

    private function activateStarterPlan(): void
    {
        $plan = SubscriptionPlan::query()->create([
            'code' => 'accounting-starter-payments',
            'name' => 'Starter Payments',
            'price' => 100000,
            'currency' => 'IDR',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'features' => [
                PlanFeature::ACCOUNTING => true,
                PlanFeature::ADVANCED_REPORTS => false,
            ],
            'limits' => [],
            'meta' => ['product_line' => 'accounting'],
        ]);

        TenantSubscription::query()->create([
            'tenant_id' => 1,
            'subscription_plan_id' => $plan->id,
            'product_line' => 'accounting',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    private function finalizedSale(User $user): Sale
    {
        $contact = Contact::query()->create([
            'type' => 'individual',
            'name' => 'Payment Standard Customer',
            'mobile' => '628123456700',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'tenant_id' => 1,
            'type' => 'simple',
            'name' => 'Payment Standard Product',
            'slug' => 'payment-standard-' . uniqid(),
            'sku' => 'PAY-ST-' . uniqid(),
            'cost_price' => 5000,
            'sell_price' => 10000,
            'is_active' => true,
            'track_stock' => true,
        ]);

        $sale = app(CreateDraftSaleAction::class)->execute([
            'contact_id' => $contact->id,
            'source' => 'manual',
            'payment_status' => 'unpaid',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'items' => [[
                'product_id' => $product->id,
                'qty' => 1,
                'unit_price' => 10000,
                'discount_total' => 0,
                'tax_total' => 0,
            ]],
        ], $user);

        return app(FinalizeSaleAction::class)->execute($sale, [
            'payment_status' => 'unpaid',
        ], $user);
    }
}
