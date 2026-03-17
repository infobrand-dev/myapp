<?php

namespace App\Modules\Sales\Repositories;

use App\Modules\Sales\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SaleRepository
{
    public function paginateForIndex(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Sale::query()
            ->with(['contact', 'creator'])
            ->withCount('items');

        $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForDetail(Sale $sale): Sale
    {
        return $sale->load([
            'contact.company',
            'items.product',
            'items.variant',
            'statusHistories.actor',
            'voidLogs.actor',
            'creator',
            'updater',
            'finalizer',
            'voider',
            'canceller',
        ]);
    }

    public function findForEdit(Sale $sale): Sale
    {
        return $sale->load([
            'contact',
            'items.product',
            'items.variant',
        ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('sale_number', 'like', "%{$search}%")
                    ->orWhere('external_reference', 'like', "%{$search}%")
                    ->orWhere('customer_name_snapshot', 'like', "%{$search}%")
                    ->orWhereHas('contact', fn (Builder $contact) => $contact->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('items', function (Builder $item) use ($search) {
                        $item->where('product_name_snapshot', 'like', "%{$search}%")
                            ->orWhere('variant_name_snapshot', 'like', "%{$search}%")
                            ->orWhere('sku_snapshot', 'like', "%{$search}%");
                    });
            });
        }

        foreach (['status', 'payment_status', 'source'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['contact_id'])) {
            $query->where('contact_id', $filters['contact_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }
    }
}
