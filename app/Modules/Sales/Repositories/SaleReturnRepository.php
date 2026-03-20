<?php

namespace App\Modules\Sales\Repositories;

use App\Modules\Sales\Models\SaleReturn;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SaleReturnRepository
{
    public function paginateForIndex(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = SaleReturn::query()
            ->where('tenant_id', $this->tenantId())
            ->where('company_id', $this->companyId())
            ->with(['sale', 'contact', 'creator'])
            ->withCount('items');

        $this->applyFilters($query, $filters);

        if (($filters['scope'] ?? null) === 'own' && !empty($filters['user_id'])) {
            $query->where('created_by', (int) $filters['user_id']);
        }

        return $query
            ->orderByDesc('return_date')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForDetail(SaleReturn $saleReturn): SaleReturn
    {
        return $saleReturn->load([
            'sale.contact',
            'contact',
            'inventoryLocation',
            'items.saleItem',
            'items.product',
            'items.variant',
            'paymentAllocations.payment.method',
            'paymentAllocations.payment.receiver',
            'statusLogs.actor',
            'creator',
            'updater',
            'finalizer',
            'canceller',
        ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('return_number', 'like', "%{$search}%")
                    ->orWhere('sale_number_snapshot', 'like', "%{$search}%")
                    ->orWhereFullText(['customer_name_snapshot', 'reason', 'notes'], $search);
            });
        }

        foreach (['status', 'refund_status'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['sale_id'])) {
            $query->where('sale_id', $filters['sale_id']);
        }

        if (!empty($filters['contact_id'])) {
            $query->where('contact_id', $filters['contact_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('return_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('return_date', '<=', $filters['date_to']);
        }
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }

    private function companyId(): int
    {
        return (int) CompanyContext::currentId();
    }

}
