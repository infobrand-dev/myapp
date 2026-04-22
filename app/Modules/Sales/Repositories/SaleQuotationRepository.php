<?php

namespace App\Modules\Sales\Repositories;

use App\Modules\Sales\Models\SaleQuotation;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SaleQuotationRepository
{
    public function paginateForIndex(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = SaleQuotation::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->with(['contact', 'creator', 'convertedSale'])
            ->withCount('items');

        BranchContext::applyScope($query);

        $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('quotation_date')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForDetail(SaleQuotation|int $quotation): SaleQuotation
    {
        $quotationId = $quotation instanceof SaleQuotation ? $quotation->id : $quotation;

        return $this->scopedQuery()
            ->with([
                'contact.parentContact',
                'items.product',
                'items.variant',
                'convertedSale',
                'creator',
                'updater',
                'approver',
                'converter',
            ])
            ->findOrFail($quotationId);
    }

    public function findForEdit(SaleQuotation|int $quotation): SaleQuotation
    {
        $quotationId = $quotation instanceof SaleQuotation ? $quotation->id : $quotation;

        return $this->scopedQuery()
            ->with(['contact', 'items.product', 'items.variant'])
            ->findOrFail($quotationId);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('quotation_number', 'like', "%{$search}%")
                    ->orWhere('customer_name_snapshot', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('contact', fn (Builder $contact) => $contact
                        ->where('tenant_id', TenantContext::currentId())
                        ->where('name', 'like', "%{$search}%"));
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['contact_id'])) {
            $query->where('contact_id', $filters['contact_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('quotation_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('quotation_date', '<=', $filters['date_to']);
        }
    }

    private function scopedQuery(): Builder
    {
        $query = SaleQuotation::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId());

        BranchContext::applyScope($query);

        return $query;
    }
}
