<?php

namespace App\Modules\Purchases\Repositories;

use App\Modules\Purchases\Models\PurchaseRequest;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PurchaseRequestRepository
{
    public function paginateForIndex(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseRequest::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->with(['supplier', 'creator', 'convertedPurchaseOrder'])
            ->withCount('items');

        BranchContext::applyScope($query);

        $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('request_date')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForDetail($request): PurchaseRequest
    {
        $requestId = $request instanceof PurchaseRequest ? $request->id : $request;

        return $this->scopedQuery()
            ->with([
                'supplier.parentContact',
                'items.product',
                'items.variant',
                'convertedPurchaseOrder',
                'creator',
                'updater',
                'approver',
                'converter',
            ])
            ->findOrFail($requestId);
    }

    public function findForEdit($request): PurchaseRequest
    {
        $requestId = $request instanceof PurchaseRequest ? $request->id : $request;

        return $this->scopedQuery()
            ->with(['supplier', 'items.product', 'items.variant'])
            ->findOrFail($requestId);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('request_number', 'like', "%{$search}%")
                    ->orWhere('supplier_name_snapshot', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function (Builder $supplier) use ($search) {
                        $supplier->where('tenant_id', TenantContext::currentId())
                            ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['contact_id'])) {
            $query->where('contact_id', $filters['contact_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('request_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('request_date', '<=', $filters['date_to']);
        }
    }

    private function scopedQuery(): Builder
    {
        $query = PurchaseRequest::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId());

        BranchContext::applyScope($query);

        return $query;
    }
}
