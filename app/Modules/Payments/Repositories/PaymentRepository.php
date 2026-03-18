<?php

namespace App\Modules\Payments\Repositories;

use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PaymentRepository
{
    public function paginateForIndex(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Payment::query()
            ->with(['method', 'receiver', 'allocations'])
            ->withCount('allocations');

        $this->applyFilters($query, $filters);

        if (($filters['scope'] ?? null) === 'own' && !empty($filters['user_id'])) {
            $query->where('received_by', (int) $filters['user_id']);
        }

        return $query
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForDetail(Payment $payment): Payment
    {
        return $payment->load([
            'method',
            'receiver',
            'creator',
            'updater',
            'voider',
            'allocations.payable',
            'statusLogs.actor',
            'voidLogs.actor',
        ]);
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
                                $payable->where('sale_number', 'like', "%{$search}%")
                                    ->orWhere('customer_name_snapshot', 'like', "%{$search}%");
                            })->orWhereHasMorph('payable', [SaleReturn::class], function (Builder $payable) use ($search) {
                                $payable->where('return_number', 'like', "%{$search}%")
                                    ->orWhere('sale_number_snapshot', 'like', "%{$search}%")
                                    ->orWhere('customer_name_snapshot', 'like', "%{$search}%");
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
            $query->whereDate('paid_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('paid_at', '<=', $filters['date_to']);
        }
    }
}
