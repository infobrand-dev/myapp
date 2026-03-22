<?php

namespace App\Modules\Payments\Repositories;

use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PaymentRepository
{
    public function paginateForIndex(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildIndexQuery($filters);

        return $query
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function summary(array $filters): array
    {
        $query = $this->buildIndexQuery($filters);

        return [
            'total_count' => (clone $query)->count(),
            'posted_count' => (clone $query)->where('status', Payment::STATUS_POSTED)->count(),
            'voided_count' => (clone $query)->where('status', Payment::STATUS_VOIDED)->count(),
            'total_amount' => (float) ((clone $query)->sum('amount') ?: 0),
            'posted_amount' => (float) ((clone $query)->where('status', Payment::STATUS_POSTED)->sum('amount') ?: 0),
            'manual_count' => (clone $query)->where('source', Payment::SOURCE_MANUAL)->count(),
        ];
    }

    public function findForDetail(Payment $payment): Payment
    {
        return Payment::query()
            ->where('tenant_id', $this->tenantId())
            ->where('company_id', $this->companyId())
            ->with([
                'method',
                'receiver',
                'creator',
            'updater',
            'voider',
                'allocations.payable',
                'statusLogs.actor',
                'voidLogs.actor',
            ])
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->findOrFail($payment->id);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('external_reference', 'like', "%{$search}%")
                    ->orWhereHas('allocations', function (Builder $allocation) use ($search) {
                        $allocation->where(function (Builder $morphQuery) use ($search) {
                            $morphQuery->whereHasMorph('payable', [Sale::class], function (Builder $payable) use ($search) {
                                $payable->where('tenant_id', $this->tenantId())
                                    ->where('company_id', $this->companyId())
                                    ->where(function (Builder $nested) use ($search) {
                                        $nested->where('sale_number', 'like', "%{$search}%")
                                            ->orWhere('customer_name_snapshot', 'like', "%{$search}%");
                                    });
                                BranchContext::applyScope($payable);
                            })->orWhereHasMorph('payable', [SaleReturn::class], function (Builder $payable) use ($search) {
                                $payable->where('tenant_id', $this->tenantId())
                                    ->where('company_id', $this->companyId())
                                    ->where(function (Builder $nested) use ($search) {
                                        $nested->where('return_number', 'like', "%{$search}%")
                                            ->orWhere('sale_number_snapshot', 'like', "%{$search}%")
                                            ->orWhere('customer_name_snapshot', 'like', "%{$search}%");
                                    });
                                BranchContext::applyScope($payable);
                            });
                        });
                    });
            });
        }

        foreach (['status', 'source', 'payment_method_id', 'received_by'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->where('paid_at', '>=', $filters['date_from'] . ' 00:00:00');
        }

        if (!empty($filters['date_to'])) {
            $query->where('paid_at', '<=', $filters['date_to'] . ' 23:59:59');
        }
    }

    private function buildIndexQuery(array $filters): Builder
    {
        $query = Payment::query()
            ->where('tenant_id', $this->tenantId())
            ->where('company_id', $this->companyId())
            ->with(['method', 'receiver', 'allocations'])
            ->withCount('allocations');

        BranchContext::applyScope($query);
        $this->applyFilters($query, $filters);

        if (($filters['scope'] ?? null) === 'own' && !empty($filters['user_id'])) {
            $query->where('received_by', (int) $filters['user_id']);
        }

        return $query;
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
