<?php

namespace Tests\Feature\Sales;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\PaymentsServiceProvider;
use App\Modules\Products\Models\Product;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\Actions\FinalizeSaleAction;
use App\Modules\Sales\Actions\RecordSalePaymentAction;
use App\Modules\Sales\Actions\UpdateDraftSaleAction;
use App\Modules\Sales\Actions\VoidSaleAction;
use App\Modules\Sales\Events\SaleFinalized;
use App\Modules\Sales\Events\SaleVoided;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\SalesServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SalesWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_user_can_create_manual_draft_sale(): void
    {
        $user = $this->salesUser([
            'sales.create',
            'sales.view',
        ]);
        $contact = $this->customer();
        $product = $this->product('Produk Draft', 'DRAFT-001', 'produk-draft');

        $sale = app(CreateDraftSaleAction::class)->execute([
            'contact_id' => $contact->id,
            'source' => 'manual',
            'payment_status' => 'unpaid',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'notes' => 'Manual entry',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit_price' => 15000,
                    'discount_total' => 1000,
                    'tax_total' => 500,
                    'notes' => 'Baris pertama',
                ],
            ],
        ], $user);

        $sale = Sale::query()->with('items')->findOrFail($sale->id);

        $this->assertSame('draft', $sale->status);
        $this->assertSame('manual', $sale->source);
        $this->assertSame('unpaid', $sale->payment_status);
        $this->assertSame('Produk Draft', $sale->items->first()->product_name_snapshot);
        $this->assertEquals(30000.00, (float) $sale->subtotal);
        $this->assertEquals(1000.00, (float) $sale->discount_total);
        $this->assertEquals(500.00, (float) $sale->tax_total);
        $this->assertEquals(29500.00, (float) $sale->grand_total);
        $this->assertNotEmpty($sale->sale_number);
        $this->assertDatabaseCount('sale_status_histories', 1);
    }

    public function test_user_can_update_draft_sale_and_totals_are_recalculated(): void
    {
        $user = $this->salesUser([
            'sales.create',
            'sales.view',
            'sales.update-draft',
        ]);
        $contact = $this->customer();
        $product = $this->product('Produk Update', 'UPD-001', 'produk-update');

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

        $sale = app(UpdateDraftSaleAction::class)->execute($sale, [
            'contact_id' => $contact->id,
            'source' => 'manual',
            'payment_status' => 'partial',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 3,
                    'unit_price' => 12000,
                    'discount_total' => 2000,
                    'tax_total' => 1000,
                ],
            ],
        ], $user);

        $sale->refresh();

        $this->assertSame('draft', $sale->status);
        $this->assertSame('partial', $sale->payment_status);
        $this->assertEquals(36000.00, (float) $sale->subtotal);
        $this->assertEquals(2000.00, (float) $sale->discount_total);
        $this->assertEquals(1000.00, (float) $sale->tax_total);
        $this->assertEquals(35000.00, (float) $sale->grand_total);
        $this->assertDatabaseCount('sale_items', 1);
    }

    public function test_finalizing_sale_dispatches_event_and_prevents_future_draft_edit(): void
    {
        Event::fake([SaleFinalized::class]);

        $user = $this->salesUser([
            'sales.create',
            'sales.view',
            'sales.update-draft',
            'sales.finalize',
        ]);
        $contact = $this->customer();
        $product = $this->product('Produk Final', 'FIN-001', 'produk-final');

        $sale = app(CreateDraftSaleAction::class)->execute([
            'contact_id' => $contact->id,
            'source' => 'manual',
            'payment_status' => 'unpaid',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit_price' => 20000,
                    'discount_total' => 0,
                    'tax_total' => 2000,
                ],
            ],
        ], $user);

        $sale = app(FinalizeSaleAction::class)->execute($sale, [
            'payment_status' => 'paid',
            'reason' => 'Checkout complete',
        ], $user);

        $sale->refresh();

        $this->assertSame('finalized', $sale->status);
        $this->assertSame('paid', $sale->payment_status);
        $this->assertNotNull($sale->finalized_at);
        Event::assertDispatched(SaleFinalized::class, function ($event) use ($sale) {
            return (int) $event->sale->id === (int) $sale->id;
        });

        $this->expectException(ValidationException::class);
        app(UpdateDraftSaleAction::class)->execute($sale, [
            'contact_id' => $contact->id,
            'source' => 'manual',
            'payment_status' => 'paid',
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
    }

    public function test_voiding_finalized_sale_requires_reason_and_dispatches_event(): void
    {
        Event::fake([SaleVoided::class]);

        $user = $this->salesUser([
            'sales.create',
            'sales.view',
            'sales.finalize',
            'sales.void',
        ]);
        $contact = $this->customer();
        $product = $this->product('Produk Void', 'VOID-001', 'produk-void');

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
                    'unit_price' => 50000,
                    'discount_total' => 0,
                    'tax_total' => 0,
                ],
            ],
        ], $user);

        $sale = app(FinalizeSaleAction::class)->execute($sale, [
            'payment_status' => 'paid',
        ], $user);

        try {
            app(VoidSaleAction::class)->execute($sale, [], $user);
            $this->fail('Void tanpa reason seharusnya gagal.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }

        $sale = app(VoidSaleAction::class)->execute($sale, [
            'reason' => 'Customer cancellation',
        ], $user);

        $sale->refresh();

        $this->assertSame('voided', $sale->status);
        $this->assertSame('Customer cancellation', $sale->void_reason);
        $this->assertNotNull($sale->voided_at);
        $this->assertDatabaseCount('sale_void_logs', 1);
        Event::assertDispatched(SaleVoided::class, function ($event) use ($sale) {
            return (int) $event->sale->id === (int) $sale->id;
        });
    }

    public function test_pos_request_is_idempotent_by_source_and_external_reference(): void
    {
        $user = $this->salesUser([
            'sales.create',
            'sales.view',
        ]);
        $contact = $this->customer();
        $product = $this->product('Produk POS', 'POS-001', 'produk-pos');

        $payload = [
            'contact_id' => $contact->id,
            'source' => 'pos',
            'external_reference' => 'POS-ORDER-1001',
            'payment_status' => 'paid',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit_price' => 25000,
                    'discount_total' => 0,
                    'tax_total' => 0,
                ],
            ],
        ];

        app(CreateDraftSaleAction::class)->execute($payload, $user);
        app(CreateDraftSaleAction::class)->execute($payload, $user);

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseHas('sales', [
            'source' => 'pos',
            'external_reference' => 'POS-ORDER-1001',
        ]);
    }

    public function test_recording_payments_updates_sale_payment_summary(): void
    {
        $user = $this->salesUser([
            'sales.create',
            'sales.finalize',
            'payments.create',
        ]);
        $contact = $this->customer();
        $product = $this->product('Produk Payment', 'PAY-001', 'produk-payment');

        $sale = app(CreateDraftSaleAction::class)->execute([
            'contact_id' => $contact->id,
            'source' => 'manual',
            'payment_status' => 'unpaid',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit_price' => 20000,
                    'discount_total' => 0,
                    'tax_total' => 0,
                ],
            ],
        ], $user);

        $sale = app(FinalizeSaleAction::class)->execute($sale, [
            'payment_status' => 'unpaid',
        ], $user);

        app(RecordSalePaymentAction::class)->execute($sale, [
            'payment_method' => 'cash',
            'amount' => 10000,
            'payment_date' => now()->format('Y-m-d H:i:s'),
        ], $user);

        $sale->refresh();
        $this->assertSame('partial', $sale->payment_status);
        $this->assertEquals(10000.00, (float) $sale->paid_total);
        $this->assertEquals(30000.00, (float) $sale->balance_due);

        app(RecordSalePaymentAction::class)->execute($sale, [
            'payment_method' => 'bank_transfer',
            'amount' => 30000,
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'reference_number' => 'TRX-001',
        ], $user);

        $sale->refresh();
        $this->assertSame('paid', $sale->payment_status);
        $this->assertEquals(40000.00, (float) $sale->paid_total);
        $this->assertEquals(0.00, (float) $sale->balance_due);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseCount('payment_allocations', 2);
        $this->assertDatabaseHas('payments', [
            'status' => Payment::STATUS_POSTED,
            'reference_number' => 'TRX-001',
        ]);
    }

    public function test_pos_source_requires_external_reference(): void
    {
        $user = $this->salesUser([
            'sales.create',
        ]);
        $product = $this->product('Produk POS Ref', 'POS-REF-001', 'produk-pos-ref');

        $response = $this->actingAs($user)->post('/sales', [
            'source' => 'pos',
            'payment_status' => 'paid',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'items' => [
                [
                    'sellable_key' => 'product:' . $product->id,
                    'qty' => 1,
                    'unit_price' => 10000,
                    'discount_total' => 0,
                    'tax_total' => 0,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('external_reference');
        $this->assertDatabaseCount('sales', 0);
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
            'name' => 'Customer Test',
            'email' => 'customer@example.com',
            'mobile' => '628123456789',
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
