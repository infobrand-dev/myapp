<?php

namespace App\Modules\Sales\Database\Seeders;

use App\Models\User;
use App\Modules\Contacts\Database\Seeders\ContactSampleSeeder;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Products\Database\Seeders\ProductSampleSeeder;
use App\Modules\Products\Models\Product;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Illuminate\Database\Seeder;

class SaleSampleSeeder extends Seeder
{
    public function run(): void
    {
        (new ProductSampleSeeder())->run();
        (new ContactSampleSeeder())->run();

        $user = User::query()->where('email', 'superadmin@myapp.test')->first() ?? User::query()->first();
        $contact = Contact::query()->where('email', 'procurement@demo-nusantara.test')->first();
        $product = Product::query()->where('sku', 'DEMO-COFFEE-250')->first();

        $sale = Sale::query()->updateOrCreate(
            ['sale_number' => 'SAL-DEMO-001'],
            [
                'external_reference' => 'WEB-DEMO-001',
                'contact_id' => $contact?->id,
                'customer_name_snapshot' => $contact?->name,
                'customer_email_snapshot' => $contact?->email,
                'customer_phone_snapshot' => $contact?->mobile,
                'customer_address_snapshot' => trim(implode(', ', array_filter([$contact?->street, $contact?->city, $contact?->country]))),
                'customer_snapshot' => ['seeded' => true],
                'status' => Sale::STATUS_FINALIZED,
                'payment_status' => Sale::PAYMENT_UNPAID,
                'source' => Sale::SOURCE_MANUAL,
                'transaction_date' => now()->subDay(),
                'finalized_at' => now()->subDay(),
                'subtotal' => 130000,
                'discount_total' => 5000,
                'tax_total' => 0,
                'grand_total' => 125000,
                'paid_total' => 0,
                'balance_due' => 125000,
                'currency_code' => 'IDR',
                'notes' => 'Transaksi sample untuk modul Sales.',
                'totals_snapshot' => ['seeded' => true],
                'meta' => ['seeded' => true],
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
                'finalized_by' => $user?->id,
            ]
        );

        if ($product) {
            SaleItem::query()->updateOrCreate(
                ['sale_id' => $sale->id, 'line_no' => 1],
                [
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'product_name_snapshot' => $product->name,
                    'variant_name_snapshot' => null,
                    'sku_snapshot' => $product->sku,
                    'barcode_snapshot' => $product->barcode,
                    'unit_snapshot' => optional($product->unit)->code,
                    'product_snapshot' => ['seeded' => true],
                    'qty' => 2,
                    'unit_price' => 65000,
                    'line_subtotal' => 130000,
                    'discount_total' => 5000,
                    'tax_total' => 0,
                    'line_total' => 125000,
                    'pricing_snapshot' => ['seeded' => true],
                    'sort_order' => 1,
                ]
            );
        }
    }
}
