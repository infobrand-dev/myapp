<?php

namespace Tests\Feature\Sales;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Payments\PaymentsServiceProvider;
use App\Modules\Products\Models\Product;
use App\Modules\Products\ProductsServiceProvider;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\SalesServiceProvider;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SalesReadScopeTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $allowedBranch;
    private Branch $blockedBranch;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ProductsServiceProvider::class);
        $this->app->register(PaymentsServiceProvider::class);
        $this->app->register(SalesServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Contacts/database/migrations',
            '--realpath' => false,
        ])->run();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Products/database/migrations',
            '--realpath' => false,
        ])->run();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Payments/database/migrations',
            '--realpath' => false,
        ])->run();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Sales/database/migrations',
            '--realpath' => false,
        ])->run();

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->company = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Scoped Sales',
            'slug' => 'pt-scoped-sales',
            'code' => 'SCP',
            'is_active' => true,
        ]);

        $this->allowedBranch = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'name' => 'Allowed Branch',
            'slug' => 'allowed-sales-branch',
            'code' => 'ASB',
            'is_active' => true,
        ]);

        $this->blockedBranch = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'name' => 'Blocked Branch',
            'slug' => 'blocked-sales-branch',
            'code' => 'BSB',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['tenant_id' => 1]);

        foreach (['sales.view', 'sales.update-draft', 'sales.print'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo(['sales.view', 'sales.update-draft', 'sales.print']);

        \DB::table('user_companies')->insert([
            'tenant_id' => 1,
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('user_branches')->insert([
            'tenant_id' => 1,
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->allowedBranch->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId($this->company->id);
        BranchContext::setCurrentId($this->allowedBranch->id);
    }

    public function test_show_edit_and_invoice_return_404_for_sale_outside_active_branch_scope(): void
    {
        $sale = $this->draftSaleInBranch($this->blockedBranch->id);

        $session = [
            'company_id' => $this->company->id,
            'company_slug' => $this->company->slug,
            'branch_id' => $this->allowedBranch->id,
            'branch_slug' => $this->allowedBranch->slug,
        ];

        $this->actingAs($this->user)->withSession($session)->get('/sales/' . $sale->id)->assertNotFound();
        $this->actingAs($this->user)->withSession($session)->get('/sales/' . $sale->id . '/edit')->assertNotFound();
        $this->actingAs($this->user)->withSession($session)->get('/sales/' . $sale->id . '/invoice')->assertNotFound();
    }

    public function test_show_edit_and_invoice_allow_sale_inside_active_branch_scope(): void
    {
        $sale = $this->draftSaleInBranch($this->allowedBranch->id);

        $session = [
            'company_id' => $this->company->id,
            'company_slug' => $this->company->slug,
            'branch_id' => $this->allowedBranch->id,
            'branch_slug' => $this->allowedBranch->slug,
        ];

        $this->actingAs($this->user)->withSession($session)->get('/sales/' . $sale->id)->assertOk();
        $this->actingAs($this->user)->withSession($session)->get('/sales/' . $sale->id . '/edit')->assertOk();
        $this->actingAs($this->user)->withSession($session)->get('/sales/' . $sale->id . '/invoice')->assertOk();
    }

    private function draftSaleInBranch(int $branchId)
    {
        CompanyContext::setCurrentId($this->company->id);
        BranchContext::setCurrentId($branchId);

        $contact = Contact::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'branch_id' => $branchId,
            'type' => 'individual',
            'name' => 'Scoped Customer ' . uniqid(),
            'mobile' => '628123456789',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'tenant_id' => 1,
            'type' => 'simple',
            'name' => 'Scoped Product ' . uniqid(),
            'slug' => 'scoped-product-' . uniqid(),
            'sku' => 'SCP-' . uniqid(),
            'cost_price' => 5000,
            'sell_price' => 10000,
            'is_active' => true,
            'track_stock' => true,
        ]);

        return app(CreateDraftSaleAction::class)->execute([
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
        ], $this->user);
    }
}
