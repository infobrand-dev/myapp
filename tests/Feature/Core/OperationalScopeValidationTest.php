<?php

namespace Tests\Feature\Core;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Inventory\InventoryServiceProvider;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Payments\PaymentsServiceProvider;
use App\Modules\Products\Models\Product;
use App\Modules\Products\ProductsServiceProvider;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\Models\PurchaseItem;
use App\Modules\Purchases\PurchasesServiceProvider;
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
use Tests\TestCase;

class OperationalScopeValidationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branchA;
    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ProductsServiceProvider::class);
        $this->app->register(InventoryServiceProvider::class);
        $this->app->register(PaymentsServiceProvider::class);
        $this->app->register(SalesServiceProvider::class);
        $this->app->register(PurchasesServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Contacts/database/migrations',
            '--realpath' => false,
        ])->run();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Products/database/migrations',
            '--realpath' => false,
        ])->run();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Inventory/database/migrations',
            '--realpath' => false,
        ])->run();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Payments/database/migrations',
            '--realpath' => false,
        ])->run();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/PointOfSale/database/migrations',
            '--realpath' => false,
        ])->run();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Sales/database/migrations',
            '--realpath' => false,
        ])->run();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Purchases/Database/Migrations',
            '--realpath' => false,
        ])->run();

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->company = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Ops Scope',
            'slug' => 'pt-ops-scope',
            'code' => 'OPS',
            'is_active' => true,
            'meta' => [],
        ]);

        $this->branchA = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'name' => 'Branch A',
            'slug' => 'branch-a',
            'code' => 'BRA',
            'is_active' => true,
            'meta' => [],
        ]);

        $this->branchB = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'name' => 'Branch B',
            'slug' => 'branch-b',
            'code' => 'BRB',
            'is_active' => true,
            'meta' => [],
        ]);

        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId($this->company->id);
        BranchContext::setCurrentId(null);
    }

    public function test_sales_return_rejects_sale_from_different_branch_scope(): void
    {
        $user = $this->userWithPermissions([
            'sales.create',
            'sales.finalize',
            'sales_return.create',
        ]);

        [$sale, $saleItem] = $this->finalizedSaleInBranch($user, $this->branchA);

        $this->actingAs($user)
            ->withSession([
                'company_id' => $this->company->id,
                'company_slug' => $this->company->slug,
                'branch_id' => $this->branchB->id,
                'branch_slug' => $this->branchB->slug,
            ])
            ->post('/sales/returns', [
                'sale_id' => $sale->id,
                'reason' => 'Cross-branch should fail',
                'items' => [
                    [
                        'sale_item_id' => $saleItem->id,
                        'qty_returned' => 1,
                    ],
                ],
            ])
            ->assertSessionHasErrors('sale_id');
    }

    public function test_purchase_receive_rejects_inventory_location_from_different_branch_scope(): void
    {
        $user = $this->userWithPermissions([
            'purchases.receive',
            'purchases.view_all',
        ]);

        $product = Product::query()->create([
            'tenant_id' => 1,
            'type' => 'simple',
            'name' => 'Purchase Product',
            'slug' => 'purchase-product',
            'sku' => 'PUR-001',
            'cost_price' => 10000,
            'sell_price' => 15000,
            'is_active' => true,
            'track_stock' => true,
        ]);

        $locationA = InventoryLocation::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'code' => 'LOC-A',
            'name' => 'Location A',
            'type' => 'warehouse',
            'is_default' => false,
            'is_active' => true,
        ]);

        $purchase = Purchase::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'branch_id' => $this->branchB->id,
            'purchase_number' => 'PO-B-001',
            'status' => Purchase::STATUS_CONFIRMED,
            'payment_status' => Purchase::PAYMENT_UNPAID,
            'purchase_date' => now(),
            'currency_code' => 'IDR',
            'subtotal' => 10000,
            'grand_total' => 10000,
            'balance_due' => 10000,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $purchaseItem = PurchaseItem::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'branch_id' => $this->branchB->id,
            'purchase_id' => $purchase->id,
            'line_no' => 1,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 2,
            'qty_received' => 0,
            'unit_cost' => 5000,
            'line_subtotal' => 10000,
            'discount_total' => 0,
            'tax_total' => 0,
            'line_total' => 10000,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->withSession([
                'company_id' => $this->company->id,
                'company_slug' => $this->company->slug,
                'branch_id' => $this->branchB->id,
                'branch_slug' => $this->branchB->slug,
            ])
            ->post('/purchases/' . $purchase->id . '/receive', [
                'inventory_location_id' => $locationA->id,
                'items' => [
                    [
                        'purchase_item_id' => $purchaseItem->id,
                        'qty_received' => 1,
                    ],
                ],
            ])
            ->assertSessionHasErrors('inventory_location_id');
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['tenant_id' => 1]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function finalizedSaleInBranch(User $user, Branch $branch): array
    {
        $contact = Contact::query()->create([
            'tenant_id' => 1,
            'type' => 'individual',
            'name' => 'Scoped Customer',
            'mobile' => '628123456700',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'tenant_id' => 1,
            'type' => 'simple',
            'name' => 'Scoped Sale Product',
            'slug' => 'scoped-sale-product-' . uniqid(),
            'sku' => 'SALE-' . uniqid(),
            'cost_price' => 10000,
            'sell_price' => 15000,
            'is_active' => true,
            'track_stock' => true,
        ]);

        BranchContext::setCurrentId($branch->id);
        CompanyContext::setCurrentId($this->company->id);

        $sale = app(CreateDraftSaleAction::class)->execute([
            'contact_id' => $contact->id,
            'source' => 'manual',
            'payment_status' => 'unpaid',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'unit_price' => 15000,
                    'discount_total' => 0,
                    'tax_total' => 0,
                ],
            ],
        ], $user);

        $sale = app(FinalizeSaleAction::class)->execute($sale, [
            'payment_status' => 'unpaid',
        ], $user);

        $sale->load('items');

        return [$sale, $sale->items->first()];
    }
}
