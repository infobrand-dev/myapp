<?php

namespace App\Modules\Sales\Services;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SaleReturnLookupService
{
    public function saleOptions(): Collection
    {
        $query = Sale::query()
            ->with('items')
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('status', Sale::STATUS_FINALIZED)
            ->orderByDesc('transaction_date')
            ->limit(100);

        BranchContext::applyScope($query);

        return $query->get();
    }

    public function inventoryLocations(): Collection
    {
        if (!class_exists(InventoryLocation::class) || !Schema::hasTable('inventory_locations')) {
            return collect();
        }

        return InventoryLocation::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('is_active', true)
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_default']);
    }

    public function customerOptions(): Collection
    {
        return Contact::query()
            ->tap(fn ($query) => ContactScope::applyVisibilityScope($query))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function statusOptions(): array
    {
        return [
            SaleReturn::STATUS_DRAFT => 'Draft',
            SaleReturn::STATUS_FINALIZED => 'Finalized',
            SaleReturn::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function refundStatusOptions(): array
    {
        return [
            SaleReturn::REFUND_NOT_REQUIRED => 'Not Required',
            SaleReturn::REFUND_PENDING => 'Pending',
            SaleReturn::REFUND_PARTIAL => 'Partial',
            SaleReturn::REFUND_REFUNDED => 'Refunded',
            SaleReturn::REFUND_SKIPPED => 'Skipped',
        ];
    }

    public function inventoryStatusOptions(): array
    {
        return [
            SaleReturn::INVENTORY_PENDING => 'Pending',
            SaleReturn::INVENTORY_COMPLETED => 'Completed',
            SaleReturn::INVENTORY_SKIPPED => 'Skipped',
            SaleReturn::INVENTORY_FAILED => 'Failed',
        ];
    }
}
