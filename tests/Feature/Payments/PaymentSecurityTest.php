<?php

namespace Tests\Feature\Payments;

use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\PaymentsServiceProvider;
use App\Modules\Products\Models\Product;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\Actions\FinalizeSaleAction;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\SalesServiceProvider;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BootstrapsModuleContext;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use BootstrapsModuleContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_user_cannot_void_payment_owned_by_another_user(): void
    {
        $owner = $this->userWithPermissions(['sales.create', 'sales.finalize', 'payments.create']);
        $attacker = $this->userWithPermissions(['payments.void', 'payments.view', 'payments.view_own']);
        $sale = $this->finalizedSale($owner);
        $payment = app(CreatePaymentAction::class)->execute([
            'payment_method_id' => PaymentMethod::query()->where('code', 'cash')->value('id'),
            'amount' => 10000,
            'allocations' => [[
                'payable_type' => 'sale',
                'payable_id' => $sale->id,
                'amount' => 10000,
            ]],
        ], $owner);

        $response = $this->actingAs($attacker)->post('/payments/' . $payment->id . '/void', [
            'reason' => 'Not allowed',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'posted',
        ]);
    }

    public function test_user_without_assign_receiver_permission_cannot_set_received_by(): void
    {
        $creator = $this->userWithPermissions(['sales.create', 'sales.finalize', 'payments.create']);
        $otherUser = User::factory()->create();
        $sale = $this->finalizedSale($creator);

        $response = $this->actingAs($creator)->post('/payments', [
            'payment_method_id' => PaymentMethod::query()->where('code', 'cash')->value('id'),
            'amount' => 10000,
            'received_by' => $otherUser->id,
            'allocations' => [[
                'payable_type' => 'sale',
                'payable_id' => $sale->id,
                'amount' => 10000,
            ]],
        ]);

        $response->assertSessionHasErrors('received_by');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_user_cannot_create_payment_for_branch_outside_membership(): void
    {
        $company = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Payments',
            'slug' => 'pt-payments',
            'code' => 'PAY',
            'is_active' => true,
        ]);

        $allowedBranch = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $company->id,
            'name' => 'Allowed Branch',
            'slug' => 'allowed-branch',
            'code' => 'ALW',
            'is_active' => true,
        ]);

        $blockedBranch = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $company->id,
            'name' => 'Blocked Branch',
            'slug' => 'blocked-branch',
            'code' => 'BLK',
            'is_active' => true,
        ]);

        $creator = $this->userWithPermissions(['sales.create', 'sales.finalize', 'payments.create']);
        $sale = $this->finalizedSale($creator);

        \DB::table('user_companies')->insert([
            'tenant_id' => 1,
            'user_id' => $creator->id,
            'company_id' => $company->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('user_branches')->insert([
            'tenant_id' => 1,
            'user_id' => $creator->id,
            'company_id' => $company->id,
            'branch_id' => $allowedBranch->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($creator)
            ->withSession([
                'company_id' => $company->id,
                'company_slug' => $company->slug,
            ])
            ->post('/payments', [
                'payment_method_id' => PaymentMethod::query()->where('code', 'cash')->value('id'),
                'amount' => 10000,
                'branch_id' => $blockedBranch->id,
                'allocations' => [[
                    'payable_type' => 'sale',
                    'payable_id' => $sale->id,
                    'amount' => 10000,
                ]],
            ]);

        $response->assertSessionHasErrors('branch_id');
        $this->assertDatabaseCount('payments', 0);
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function finalizedSale(User $user): Sale
    {
        $contact = Contact::query()->create([
            'type' => 'individual',
            'name' => 'Payments Customer',
            'mobile' => '628123456782',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'type' => 'simple',
            'name' => 'Payments Product',
            'slug' => 'payments-product-' . uniqid(),
            'sku' => 'PAYSEC-' . uniqid(),
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
                'qty' => 2,
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
