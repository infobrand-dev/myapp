<?php

namespace Tests\Feature\Crm;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Contacts\ContactsServiceProvider;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Crm\CrmServiceProvider;
use App\Modules\Crm\Models\CrmLead;
use App\Modules\Crm\Support\CrmIntegrationService;
use App\Modules\Crm\Support\CrmWonAutomationService;
use App\Modules\Finance\FinanceServiceProvider;
use App\Modules\Finance\Services\ChartOfAccountProvisioner;
use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\PaymentsServiceProvider;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Products\Models\Product;
use App\Modules\Products\ProductsServiceProvider;
use App\Modules\Sales\SalesServiceProvider;
use App\Modules\Sales\Models\Sale;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Tests\Concerns\BootstrapsModuleContext;
use Tests\Concerns\RefreshesPgsqlDatabase;
use Tests\TestCase;

class CrmAccountingBridgeTest extends TestCase
{
    use BootstrapsModuleContext;
    use RefreshesPgsqlDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('CrmAccountingBridgeTest harus dijalankan di PostgreSQL atau database non-SQLite yang setara dengan runtime aplikasi.');
        }

        $this->registerModuleProviders([
            ContactsServiceProvider::class,
            CrmServiceProvider::class,
            ProductsServiceProvider::class,
            FinanceServiceProvider::class,
            PaymentsServiceProvider::class,
            SalesServiceProvider::class,
        ]);

        $this->migrateModulePaths([
            'database/migrations',
            'app/Modules/Contacts/database/migrations',
            'app/Modules/Products/database/migrations',
            'app/Modules/Finance/database/migrations',
            'app/Modules/Payments/database/migrations',
            'app/Modules/Sales/database/migrations',
            'app/Modules/Crm/database/migrations',
        ]);

        Tenant::query()->firstOrCreate([
            'id' => 1,
        ], [
            'name' => 'Default Tenant',
            'slug' => 'default',
            'is_active' => true,
        ]);

        $this->bootstrapDefaultOperationalContext();
        app(ChartOfAccountProvisioner::class)->ensureDefaults(1, 1, null);
    }

    public function test_won_automation_and_payment_bridge_publish_customer_360_timeline(): void
    {
        $actor = User::factory()->create([
            'tenant_id' => 1,
        ]);

        $contact = Contact::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'type' => 'customer',
            'name' => 'PT Customer Bridge',
            'email' => 'bridge@example.test',
            'mobile' => '628333333333',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'type' => 'simple',
            'name' => 'CRM Bridge Product',
            'slug' => 'crm-bridge-product',
            'sku' => 'CRM-BRIDGE-001',
            'cost_price' => 100000,
            'sell_price' => 250000,
            'is_active' => true,
            'track_stock' => false,
        ]);

        $lead = CrmLead::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'contact_id' => $contact->id,
            'title' => 'Won Lead Enterprise',
            'stage' => 'won',
            'priority' => 'high',
            'estimated_value' => 500000,
            'currency' => 'IDR',
            'position' => 1,
            'won_at' => now(),
            'is_archived' => false,
        ]);

        $tenant = Tenant::query()->findOrFail(1);
        app(CrmIntegrationService::class)->update($tenant, [
            'on_won_enabled' => true,
            'create_sales_quotation' => true,
            'create_draft_sale' => true,
            'finalize_draft_sale' => true,
            'default_product_id' => $product->id,
        ]);

        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId(1);

        $this->actingAs($actor);

        app(CrmWonAutomationService::class)->handle($lead->fresh());

        $sale = Sale::query()->firstOrFail();
        $cashMethodId = PaymentMethod::query()
            ->where('tenant_id', 1)
            ->where('company_id', 1)
            ->where('code', 'cash')
            ->value('id');

        app(CreatePaymentAction::class)->execute([
            'payment_method_id' => $cashMethodId,
            'amount' => 500000,
            'currency_code' => 'IDR',
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'source' => 'backoffice',
            'allocations' => [[
                'payable_type' => 'sale',
                'payable_id' => $sale->id,
                'amount' => 500000,
            ]],
        ], $actor);

        $this->assertDatabaseHas('sale_quotations', [
            'tenant_id' => 1,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('sales', [
            'tenant_id' => 1,
            'contact_id' => $contact->id,
            'status' => 'finalized',
            'source' => 'crm',
        ]);
        $this->assertDatabaseHas('crm_activities', [
            'tenant_id' => 1,
            'contact_id' => $contact->id,
            'activity_type' => 'sales_quotation_created',
        ]);
        $this->assertDatabaseHas('crm_activities', [
            'tenant_id' => 1,
            'contact_id' => $contact->id,
            'activity_type' => 'sales_invoice_finalized',
        ]);
        $this->assertDatabaseHas('crm_activities', [
            'tenant_id' => 1,
            'contact_id' => $contact->id,
            'activity_type' => 'payment_posted',
        ]);
    }
}
