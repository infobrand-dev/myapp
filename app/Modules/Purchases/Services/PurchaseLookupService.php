<?php

namespace App\Modules\Purchases\Services;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Finance\Models\FinanceTaxRate;
use App\Modules\Finance\Services\TransactionTaxService;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Products\Services\ProductLookupService;
use App\Modules\Purchases\Models\Purchase;
use App\Support\BooleanQuery;
use App\Support\TenantContext;
use Illuminate\Support\Collection;

class PurchaseLookupService
{
    public function __construct(
        private readonly ProductLookupService $productLookup,
        private readonly TransactionTaxService $transactionTaxService,
    ) {
    }

    public function suppliers(): Collection
    {
        return BooleanQuery::apply(
            Contact::query()->tap(fn ($query) => ContactScope::applyVisibilityScope($query)),
            'is_active'
        )
            ->orderBy('name')
            ->get();
    }

    public function purchasables(): Collection
    {
        return $this->productLookup->forAutocomplete()->map(fn ($item) => [
            ...$item,
            'unit_cost' => $item['cost_price'],
        ]);
    }

    public function inventoryLocations(): Collection
    {
        return BooleanQuery::apply(
            InventoryLocation::query()->where('tenant_id', TenantContext::currentId()),
            'is_active'
        )
            ->orderBy('name')
            ->get();
    }

    public function purchaseTaxOptions(): Collection
    {
        return $this->transactionTaxService->options(FinanceTaxRate::TYPE_PURCHASE);
    }

    public function statusOptions(): array
    {
        return [
            Purchase::STATUS_DRAFT => 'Draft',
            Purchase::STATUS_CONFIRMED => 'Confirmed',
            Purchase::STATUS_PARTIAL_RECEIVED => 'Partial Received',
            Purchase::STATUS_RECEIVED => 'Received',
            Purchase::STATUS_CANCELLED => 'Cancelled',
            Purchase::STATUS_VOIDED => 'Voided',
        ];
    }

    public function paymentStatusOptions(): array
    {
        return [
            Purchase::PAYMENT_UNPAID => 'Unpaid',
            Purchase::PAYMENT_PARTIAL => 'Partial',
            Purchase::PAYMENT_PAID => 'Paid',
            Purchase::PAYMENT_OVERPAID => 'Overpaid',
        ];
    }

    public function supplierBillStatusOptions(): array
    {
        return [
            Purchase::BILL_PENDING => 'Pending Bill',
            Purchase::BILL_RECEIVED => 'Bill Received',
            Purchase::BILL_VERIFIED => 'Bill Verified',
        ];
    }

    public function dependencyMap(): array
    {
        return [
            [
                'module' => 'products',
                'type' => 'required',
                'notes' => 'Purchases membaca master product dan variant dari Products, lalu menyimpan snapshot item saat draft/final.',
            ],
            [
                'module' => 'contacts',
                'type' => 'required',
                'notes' => 'Supplier/vendor direferensikan dari Contacts dan disimpan snapshotnya untuk menjaga histori.',
            ],
            [
                'module' => 'inventory',
                'type' => 'required',
                'notes' => 'Receiving memicu stock-in ke Inventory. Purchases tidak menyimpan balance stok.',
            ],
            [
                'module' => 'payments',
                'type' => 'required',
                'notes' => 'Status pembayaran diringkas dari allocation di Payments. Purchases tidak menyimpan domain pembayaran utama.',
            ],
        ];
    }
}
