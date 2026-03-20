<?php

namespace App\Modules\Purchases\Repositories;

use App\Modules\Purchases\Models\Purchase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PurchaseRepository
{
    public function paginateForIndex(array $filters = []): LengthAwarePaginator
    {
        return Purchase::query()
            ->where('tenant_id', $this->tenantId())
            ->with('supplier')
            ->withCount('items')
            ->when(($filters['scope'] ?? 'own') !== 'all', fn ($query) => $query->where('created_by', $filters['user_id'] ?? 0))
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('purchase_number', 'like', "%{$search}%")
                        ->orWhere('supplier_invoice_number', 'like', "%{$search}%")
                        ->orWhere('supplier_reference', 'like', "%{$search}%")
                        ->orWhereFullText(
                            ['supplier_name_snapshot', 'supplier_notes', 'notes', 'internal_notes'],
                            $search
                        );
                });
            })
            ->when(!empty($filters['contact_id']), fn ($query) => $query->where('contact_id', $filters['contact_id']))
            ->when(!empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(!empty($filters['payment_status']), fn ($query) => $query->where('payment_status', $filters['payment_status']))
            ->when(!empty($filters['date_from']), fn ($query) => $query->whereDate('purchase_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($query) => $query->whereDate('purchase_date', '<=', $filters['date_to']))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function findForDetail(Purchase $purchase): Purchase
    {
        $query = Purchase::query()
            ->where('tenant_id', $this->tenantId())
            ->with([
                'supplier.company',
                'items',
                'receipts.items.purchaseItem',
                'receipts.inventoryLocation',
                'statusHistories.actor',
                'paymentAllocations.payment.method',
                'creator',
                'updater',
                'confirmer',
                'voider',
                'canceller',
            ]);

        return $query->findOrFail($purchase->id);
    }

    public function findForEdit(Purchase $purchase): Purchase
    {
        return Purchase::query()
            ->where('tenant_id', $this->tenantId())
            ->with('items')
            ->findOrFail($purchase->id);
    }

    private function tenantId(): int
    {
        return 1;
    }
}
