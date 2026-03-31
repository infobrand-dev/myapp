<?php

namespace Tests\Feature\Core;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Contacts\ContactsServiceProvider;
use App\Modules\Conversations\ConversationsServiceProvider;
use App\Modules\Crm\CrmServiceProvider;
use App\Modules\EmailInbox\EmailInboxServiceProvider;
use App\Modules\Finance\FinanceServiceProvider;
use App\Modules\Inventory\InventoryServiceProvider;
use App\Modules\LiveChat\LiveChatServiceProvider;
use App\Modules\Payments\PaymentsServiceProvider;
use App\Modules\PointOfSale\PointOfSaleServiceProvider;
use App\Modules\Products\ProductsServiceProvider;
use App\Modules\Purchases\PurchasesServiceProvider;
use App\Modules\Sales\SalesServiceProvider;
use App\Modules\SocialMedia\SocialMediaServiceProvider;
use App\Modules\WhatsAppApi\WhatsAppApiServiceProvider;
use App\Modules\WhatsAppWeb\WhatsAppWebServiceProvider;
use App\Support\CorePermissions;
use App\Support\TenantRoleProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DefaultTenantRolePresetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Tenant::query()->firstOrCreate(['slug' => 'default'], [
            'name' => 'Default',
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissionSets() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function test_provisioner_creates_standard_roles_with_expected_permissions(): void
    {
        app(TenantRoleProvisioner::class)->ensureForTenant(1);

        $customerService = Role::query()->where('tenant_id', 1)->where('name', 'Customer Service')->first();
        $sales = Role::query()->where('tenant_id', 1)->where('name', 'Sales')->first();
        $cashier = Role::query()->where('tenant_id', 1)->where('name', 'Cashier')->first();
        $inventoryStaff = Role::query()->where('tenant_id', 1)->where('name', 'Inventory Staff')->first();
        $financeStaff = Role::query()->where('tenant_id', 1)->where('name', 'Finance Staff')->first();

        $this->assertNotNull($customerService);
        $this->assertNotNull($sales);
        $this->assertNotNull($cashier);
        $this->assertNotNull($inventoryStaff);
        $this->assertNotNull($financeStaff);

        $this->assertTrue($customerService->hasPermissionTo('conversations.view'));
        $this->assertTrue($customerService->hasPermissionTo('crm.update'));
        $this->assertTrue($customerService->hasPermissionTo('contacts.update'));
        $this->assertTrue($customerService->hasPermissionTo('email_inbox.send'));
        $this->assertTrue($customerService->hasPermissionTo('whatsapp_web.send'));
        $this->assertFalse($customerService->hasPermissionTo('whatsapp_api.manage_settings'));

        $this->assertTrue($sales->hasPermissionTo('conversations.reply'));
        $this->assertTrue($sales->hasPermissionTo('crm.create'));
        $this->assertTrue($sales->hasPermissionTo('sales.finalize'));
        $this->assertFalse($sales->hasPermissionTo('payments.manage_methods'));

        $this->assertTrue($cashier->hasPermissionTo('pos.checkout'));
        $this->assertTrue($cashier->hasPermissionTo('payments.create'));
        $this->assertFalse($cashier->hasPermissionTo('products.create'));

        $this->assertTrue($inventoryStaff->hasPermissionTo('products.update'));
        $this->assertTrue($inventoryStaff->hasPermissionTo('inventory.manage-stock-transfer'));
        $this->assertTrue($inventoryStaff->hasPermissionTo('purchases.receive'));
        $this->assertFalse($inventoryStaff->hasPermissionTo('finance.manage-categories'));

        $this->assertTrue($financeStaff->hasPermissionTo('payments.manage_methods'));
        $this->assertTrue($financeStaff->hasPermissionTo('finance.manage-categories'));
        $this->assertFalse($financeStaff->hasPermissionTo('pos.checkout'));
    }

    /**
     * @return array<int, string>
     */
    private function permissionSets(): array
    {
        return array_values(array_unique(array_merge(
            CorePermissions::PERMISSIONS,
            ConversationsServiceProvider::PERMISSIONS,
            ContactsServiceProvider::PERMISSIONS,
            CrmServiceProvider::PERMISSIONS,
            LiveChatServiceProvider::PERMISSIONS,
            SocialMediaServiceProvider::PERMISSIONS,
            WhatsAppApiServiceProvider::PERMISSIONS,
            WhatsAppWebServiceProvider::PERMISSIONS,
            EmailInboxServiceProvider::PERMISSIONS,
            SalesServiceProvider::PERMISSIONS,
            PaymentsServiceProvider::PERMISSIONS,
            PointOfSaleServiceProvider::PERMISSIONS,
            ProductsServiceProvider::PERMISSIONS,
            InventoryServiceProvider::PERMISSIONS,
            PurchasesServiceProvider::PERMISSIONS,
            FinanceServiceProvider::PERMISSIONS,
        )));
    }
}
