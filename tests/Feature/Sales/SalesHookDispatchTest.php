<?php

namespace Tests\Feature\Sales;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Products\Models\Product;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\Actions\FinalizeSaleAction;
use App\Modules\Sales\Actions\VoidSaleAction;
use App\Modules\Sales\SalesServiceProvider;
use App\Support\HookManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SalesHookDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
            '--path' => 'app/Modules/Sales/database/migrations',
            '--realpath' => false,
        ])->run();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_sales_finalized_and_voided_hooks_are_dispatched(): void
    {
        $user = $this->salesUser();
        $contact = $this->customer();
        $product = $this->product();

        $finalizedPayloads = [];
        $voidedPayloads = [];

        $hooks = $this->app->make(HookManager::class);
        $hooks->register('sales.finalized', 'test.finalized', function (array $context) use (&$finalizedPayloads) {
            $finalizedPayloads[] = $context['payload'];

            return null;
        });
        $hooks->register('sales.voided', 'test.voided', function (array $context) use (&$voidedPayloads) {
            $voidedPayloads[] = $context['payload'];

            return null;
        });

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
                    'unit_price' => 10000,
                    'discount_total' => 0,
                    'tax_total' => 0,
                ],
            ],
        ], $user);

        $sale = app(FinalizeSaleAction::class)->execute($sale, [
            'payment_status' => 'paid',
            'reason' => 'Hook finalized',
        ], $user);

        $sale = app(VoidSaleAction::class)->execute($sale, [
            'reason' => 'Hook voided',
        ], $user);

        $this->assertCount(1, $finalizedPayloads);
        $this->assertCount(1, $voidedPayloads);
        $this->assertSame('finalized', $finalizedPayloads[0]['status']);
        $this->assertSame('voided', $voidedPayloads[0]['status']);
        $this->assertSame($sale->sale_number, $voidedPayloads[0]['sale_number']);
    }

    private function salesUser(): User
    {
        $user = User::factory()->create();

        foreach (['sales.create', 'sales.finalize', 'sales.void'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo(['sales.create', 'sales.finalize', 'sales.void']);

        return $user;
    }

    private function customer(): Contact
    {
        return Contact::query()->create([
            'type' => 'individual',
            'name' => 'Hook Customer',
            'mobile' => '628123456781',
            'is_active' => true,
        ]);
    }

    private function product(): Product
    {
        return Product::query()->create([
            'type' => 'simple',
            'name' => 'Hook Product',
            'slug' => 'hook-product',
            'sku' => 'HOOK-001',
            'cost_price' => 5000,
            'sell_price' => 10000,
            'is_active' => true,
            'track_stock' => true,
        ]);
    }
}
