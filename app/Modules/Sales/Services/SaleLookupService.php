<?php

namespace App\Modules\Sales\Services;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Modules\Sales\Models\Sale;
use App\Support\BooleanQuery;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\CurrencySettingsResolver;
use App\Support\MoneyFormatter;
use App\Support\TenantContext;
use Illuminate\Support\Collection;

class SaleLookupService
{
    public function __construct(
        private readonly MoneyFormatter $money,
        private readonly CurrencySettingsResolver $currencies,
    ) {
    }

    public function customers(): Collection
    {
        return BooleanQuery::apply(
            Contact::query()->tap(fn ($query) => ContactScope::applyVisibilityScope($query)),
            'is_active'
        )
            ->orderBy('name')
            ->get();
    }

    public function sellables(): Collection
    {
        $defaultCurrency = $this->currencies->defaultCurrency();

        $products = BooleanQuery::apply(
            Product::query()->with([
                'unit',
                'variants' => fn ($query) => BooleanQuery::apply(
                    $query->whereNull('deleted_at')->orderBy('position'),
                    'is_active'
                ),
            ]),
            'is_active'
        )
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return $products->flatMap(function (Product $product) {
            $rows = collect([
                [
                    'key' => 'product:' . $product->id,
                    'type' => 'product',
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                        'label' => $product->name,
                        'description' => implode(' | ', array_filter([
                            'SKU: ' . $product->sku,
                            $product->unit && $product->unit->name ? 'Unit: ' . $product->unit->name : null,
                            'Harga default: ' . $this->money->format((float) $product->sell_price, $product->currency_code ?: $defaultCurrency),
                        ])),
                        'unit_price' => (float) $product->sell_price,
                    ],
                ]);

            $variants = $product->variants->map(function (ProductVariant $variant) use ($product, $defaultCurrency) {
                return [
                    'key' => 'variant:' . $variant->id,
                    'type' => 'variant',
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'label' => $product->name . ' - ' . $variant->name,
                    'description' => implode(' | ', array_filter([
                        'SKU: ' . $variant->sku,
                        $variant->attribute_summary,
                        'Harga default: ' . $this->money->format((float) $variant->sell_price, $variant->currency_code ?: $product->currency_code ?: $defaultCurrency),
                    ])),
                    'unit_price' => (float) $variant->sell_price,
                ];
            });

            return $rows->concat($variants);
        })->values();
    }

    public function statusOptions(): array
    {
        return [
            Sale::STATUS_DRAFT => 'Draft',
            Sale::STATUS_FINALIZED => 'Finalized',
            Sale::STATUS_VOIDED => 'Voided',
            Sale::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function paymentStatusOptions(): array
    {
        return [
            Sale::PAYMENT_UNPAID => 'Unpaid',
            Sale::PAYMENT_PARTIAL => 'Partial',
            Sale::PAYMENT_PAID => 'Paid',
            Sale::PAYMENT_OVERPAID => 'Overpaid',
            Sale::PAYMENT_REFUNDED => 'Refunded',
        ];
    }

    public function paymentMethodOptions(): array
    {
        return [
            PaymentMethod::CODE_CASH => 'Cash',
            PaymentMethod::CODE_BANK_TRANSFER => 'Bank Transfer',
            'card' => 'Card',
            PaymentMethod::CODE_EWALLET => 'E-Wallet',
            PaymentMethod::CODE_QRIS => 'QRIS',
            'other' => 'Other',
        ];
    }

    public function sourceOptions(): array
    {
        return [
            Sale::SOURCE_MANUAL => 'Manual',
            Sale::SOURCE_POS => 'POS',
            Sale::SOURCE_ONLINE => 'Online',
            Sale::SOURCE_API => 'API',
        ];
    }

    public function saleOptions(): Collection
    {
        $query = Sale::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('status', Sale::STATUS_FINALIZED)
            ->orderByDesc('transaction_date')
            ->limit(100);

        BranchContext::applyScope($query);

        return $query->get();
    }

    public function dependencyMap(): array
    {
        return [
            [
                'module' => 'products',
                'type' => 'required',
                'status' => 'upstream',
                'notes' => 'Master product dan variant dibaca dari module Products, tanpa menduplikasi catalog di Sales.',
            ],
            [
                'module' => 'contacts',
                'type' => 'required',
                'status' => 'upstream',
                'notes' => 'Customer diacu dari module Contacts dan disimpan sebagai snapshot saat transaksi final.',
            ],
            [
                'module' => 'payments',
                'type' => 'required',
                'status' => 'downstream',
                'notes' => 'Payment summary pada Sales harus disinkronkan dari module Payments. Sales tidak menyimpan domain pembayaran sebagai sumber utama.',
            ],
            [
                'module' => 'inventory',
                'type' => 'optional',
                'status' => 'downstream',
                'notes' => 'Mutasi stok tidak ditulis di Sales. Integrasi stok harus dilakukan oleh Inventory berdasarkan event atau orchestration terpisah.',
            ],
            [
                'module' => 'discounts',
                'type' => 'optional',
                'status' => 'downstream',
                'notes' => 'Rule diskon dievaluasi di luar Sales. Sales hanya menyimpan snapshot nominal hasil diskon pada header dan item.',
            ],
        ];
    }
}
