<?php

namespace Tests\Feature\Sales;

use App\Models\Company;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Payments\PaymentsServiceProvider;
use App\Modules\Products\Models\Product;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\SalesServiceProvider;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BootstrapsModuleContext;
use Tests\TestCase;

class SalesApiChannelTest extends TestCase
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
            'app/Modules/Sales/database/migrations',
        ]);

        $this->bootstrapDefaultOperationalContext();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_channel_api_can_create_and_finalize_sale_in_one_request(): void
    {
        $user = $this->salesUser(['sales.create', 'sales.finalize', 'payments.create']);
        Sanctum::actingAs($user);

        $contact = $this->customer();
        $product = $this->product('API POS Product', 'API-POS-001', 'api-pos-product');

        $response = $this->postJson('/api/sales/channel-transactions', [
            'contact_id' => $contact->id,
            'source' => 'pos',
            'external_reference' => 'POS-API-9001',
            'payment_status' => 'paid',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'auto_finalize' => true,
            'finalize_reason' => 'POS checkout',
            'payments' => [
                [
                    'payment_method' => 'cash',
                    'amount' => 35500,
                    'payment_date' => now()->format('Y-m-d H:i:s'),
                    'reference_number' => 'POS-PAY-1',
                ],
            ],
            'items' => [
                [
                    'sellable_key' => 'product:' . $product->id,
                    'qty' => 2,
                    'unit_price' => 17500,
                    'discount_total' => 500,
                    'tax_total' => 1000,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.source', 'pos')
            ->assertJsonPath('data.status', 'finalized')
            ->assertJsonPath('data.external_reference', 'POS-API-9001')
            ->assertJsonPath('data.totals.paid_total', 35500)
            ->assertJsonPath('data.payment_status', 'paid');

        $this->assertDatabaseHas('sales', [
            'source' => 'pos',
            'external_reference' => 'POS-API-9001',
            'status' => 'finalized',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_allocations', 1);
    }

    public function test_channel_api_returns_existing_sale_for_same_source_and_external_reference(): void
    {
        $user = $this->salesUser(['sales.create']);
        Sanctum::actingAs($user);

        $contact = $this->customer();
        $product = $this->product('API Idempotent Product', 'API-IDEMP-001', 'api-idemp-product');

        $payload = [
            'contact_id' => $contact->id,
            'source' => 'api',
            'external_reference' => 'EXT-API-1002',
            'payment_status' => 'unpaid',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'items' => [
                [
                    'sellable_key' => 'product:' . $product->id,
                    'qty' => 1,
                    'unit_price' => 25000,
                    'discount_total' => 0,
                    'tax_total' => 0,
                ],
            ],
        ];

        $first = $this->postJson('/api/sales/channel-transactions', $payload)->assertCreated();
        $second = $this->postJson('/api/sales/channel-transactions', $payload)->assertOk();

        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id')
        );
    }

    public function test_channel_api_rejects_mismatched_payload_for_existing_external_reference(): void
    {
        $user = $this->salesUser(['sales.create']);
        Sanctum::actingAs($user);

        $contact = $this->customer();
        $product = $this->product('API Mismatch Product', 'API-MISMATCH-002', 'api-mismatch-product');

        $payload = [
            'contact_id' => $contact->id,
            'source' => 'api',
            'external_reference' => 'EXT-API-2002',
            'payment_status' => 'unpaid',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'items' => [
                [
                    'sellable_key' => 'product:' . $product->id,
                    'qty' => 1,
                    'unit_price' => 25000,
                    'discount_total' => 0,
                    'tax_total' => 0,
                ],
            ],
        ];

        $this->postJson('/api/sales/channel-transactions', $payload)->assertCreated();

        $payload['items'][0]['qty'] = 2;

        $this->postJson('/api/sales/channel-transactions', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('external_reference');
    }

    private function salesUser(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function customer(): Contact
    {
        return Contact::query()->create([
            'type' => 'individual',
            'name' => 'API Customer',
            'email' => 'api.customer@example.com',
            'mobile' => '628123456780',
            'is_active' => true,
        ]);
    }

    private function product(string $name, string $sku, string $slug): Product
    {
        return Product::query()->create([
            'type' => 'simple',
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
            'cost_price' => 10000,
            'sell_price' => 15000,
            'is_active' => true,
            'track_stock' => true,
        ]);
    }
}
