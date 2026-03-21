<?php

namespace Tests\Feature\Reports;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Payments\PaymentsServiceProvider;
use App\Modules\Products\Models\Product;
use App\Modules\Products\ProductsServiceProvider;
use App\Modules\Reports\Services\SalesReportService;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\Actions\FinalizeSaleAction;
use App\Modules\Sales\SalesServiceProvider;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SalesReportScopeTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private Branch $branchA;
    private Branch $branchB;
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
            '--path' => 'app/Modules/PointOfSale/database/migrations',
            '--realpath' => false,
        ])->run();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Sales/database/migrations',
            '--realpath' => false,
        ])->run();

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->companyA = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Report Alpha',
            'slug' => 'pt-report-alpha',
            'code' => 'RPA',
            'is_active' => true,
        ]);

        $this->companyB = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Report Beta',
            'slug' => 'pt-report-beta',
            'code' => 'RPB',
            'is_active' => true,
        ]);

        $this->branchA = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->companyA->id,
            'name' => 'Branch A',
            'slug' => 'report-branch-a',
            'code' => 'RBA',
            'is_active' => true,
        ]);

        $this->branchB = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->companyA->id,
            'name' => 'Branch B',
            'slug' => 'report-branch-b',
            'code' => 'RBB',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['tenant_id' => 1]);

        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId($this->companyA->id);
        BranchContext::setCurrentId(null);
    }

    public function test_sales_report_uses_company_scope_when_branch_context_is_null(): void
    {
        $this->finalizedSale($this->companyA->id, null, 'Company Level Sale', 2, 10000);
        $this->finalizedSale($this->companyA->id, $this->branchA->id, 'Branch Sale Hidden', 1, 9000);
        $this->finalizedSale($this->companyB->id, null, 'Other Company Hidden', 1, 8000);

        CompanyContext::setCurrentId($this->companyA->id);
        BranchContext::setCurrentId(null);

        $service = app(SalesReportService::class);
        $filters = $service->filters([]);
        $summary = $service->summary($filters);
        $byCustomer = $service->byCustomer($filters);

        $this->assertSame(1, $summary['transaction_count']);
        $this->assertSame(20000.0, $summary['gross_total']);
        $this->assertSame('Company Level Sale', $byCustomer->first()->customer_name);
    }

    public function test_sales_report_uses_active_branch_scope(): void
    {
        $this->finalizedSale($this->companyA->id, $this->branchA->id, 'Branch A Sale', 2, 11000);
        $this->finalizedSale($this->companyA->id, $this->branchB->id, 'Branch B Hidden', 1, 7000);
        $this->finalizedSale($this->companyA->id, null, 'Company Level Hidden', 1, 6000);

        CompanyContext::setCurrentId($this->companyA->id);
        BranchContext::setCurrentId($this->branchA->id);

        $service = app(SalesReportService::class);
        $filters = $service->filters([]);
        $summary = $service->summary($filters);
        $byProduct = $service->byProduct($filters);

        $this->assertSame(1, $summary['transaction_count']);
        $this->assertSame(22000.0, $summary['gross_total']);
        $this->assertSame(2.0, (float) $byProduct->first()->qty_sold);
    }

    private function finalizedSale(int $companyId, ?int $branchId, string $customerName, float $qty, float $unitPrice): void
    {
        CompanyContext::setCurrentId($companyId);
        BranchContext::setCurrentId($branchId);

        $contact = Contact::query()->create([
            'tenant_id' => 1,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'type' => 'individual',
            'name' => $customerName,
            'mobile' => '628123456789',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'tenant_id' => 1,
            'type' => 'simple',
            'name' => 'Report Product ' . uniqid(),
            'slug' => 'report-product-' . uniqid(),
            'sku' => 'RPT-' . uniqid(),
            'cost_price' => 5000,
            'sell_price' => $unitPrice,
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
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount_total' => 0,
                'tax_total' => 0,
            ]],
        ], $this->user);

        app(FinalizeSaleAction::class)->execute($sale, $this->user);
    }
}
