<?php

namespace App\Modules\Discounts\Repositories;

use App\Modules\Discounts\Models\Discount;
use App\Modules\Discounts\Models\DiscountUsage;
use App\Modules\Discounts\Models\DiscountVoucher;
use App\Modules\Discounts\Support\Engine\DiscountEvaluationContext;
use App\Support\BooleanQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DiscountRepository
{
    public function paginateForIndex(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Discount::query()
            ->withCount(['vouchers', 'usages'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('internal_name', 'like', '%' . $search . '%')
                        ->orWhere('public_label', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%');
                });
            })
            ->when($filters['discount_type'] ?? null, fn (Builder $query, string $type) => $query->where('discount_type', $type))
            ->when($filters['status_view'] ?? null, fn (Builder $query, string $status) => $this->applyStatusFilter($query, $status))
            ->orderBy('priority')
            ->orderBy('sequence')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForDetail(Discount $discount): Discount
    {
        return $discount->load(['targets', 'conditions', 'vouchers', 'usages.voucher', 'usages.lines']);
    }

    public function findForEdit(Discount $discount): Discount
    {
        return $discount->load(['targets', 'conditions', 'vouchers']);
    }

    public function paginateVouchers(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return DiscountVoucher::query()
            ->with('discount:id,internal_name,public_label')
            ->withCount('usages')
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where('code', 'like', '%' . $search . '%');
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateUsages(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return DiscountUsage::query()
            ->with(['discount:id,internal_name,public_label,code', 'voucher:id,code'])
            ->when($filters['discount_id'] ?? null, fn (Builder $query, $discountId) => $query->where('discount_id', $discountId))
            ->when($filters['usage_status'] ?? null, fn (Builder $query, string $status) => $query->where('usage_status', $status))
            ->latest('applied_at')
            ->latest('evaluated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function activeForEvaluation(DiscountEvaluationContext $context): Collection
    {
        return BooleanQuery::apply(
            BooleanQuery::apply(
                Discount::query()
                    ->with(['targets', 'conditions', 'vouchers']),
                'is_active'
            ),
            'is_archived',
            false
        )
            ->where(function (Builder $query) use ($context) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $context->now->format('Y-m-d H:i:s'));
            })
            ->where(function (Builder $query) use ($context) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $context->now->format('Y-m-d H:i:s'));
            })
            ->orderBy('priority')
            ->orderBy('sequence')
            ->get();
    }

    public function usageCountForDiscount(int $discountId): int
    {
        return DiscountUsage::query()
            ->where('discount_id', $discountId)
            ->whereIn('usage_status', ['applied', 'reserved'])
            ->count();
    }

    public function usageCountForDiscountAndCustomer(int $discountId, ?string $referenceType, ?string $referenceId): int
    {
        if (!$referenceType || !$referenceId) {
            return 0;
        }

        return DiscountUsage::query()
            ->where('discount_id', $discountId)
            ->where('customer_reference_type', $referenceType)
            ->where('customer_reference_id', $referenceId)
            ->whereIn('usage_status', ['applied', 'reserved'])
            ->count();
    }

    public function usageCountForVoucher(int $voucherId): int
    {
        return DiscountUsage::query()
            ->where('voucher_id', $voucherId)
            ->whereIn('usage_status', ['applied', 'reserved'])
            ->count();
    }

    public function usageCountForVoucherAndCustomer(int $voucherId, ?string $referenceType, ?string $referenceId): int
    {
        if (!$referenceType || !$referenceId) {
            return 0;
        }

        return DiscountUsage::query()
            ->where('voucher_id', $voucherId)
            ->where('customer_reference_type', $referenceType)
            ->where('customer_reference_id', $referenceId)
            ->whereIn('usage_status', ['applied', 'reserved'])
            ->count();
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        $now = now();

        if ($status === 'active') {
            BooleanQuery::apply($query, 'is_active');
            BooleanQuery::apply($query, 'is_archived', false);
            $query
                ->where(function (Builder $nested) use ($now) {
                    $nested->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })
                ->where(function (Builder $nested) use ($now) {
                    $nested->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                });
        }

        if ($status === 'scheduled') {
            BooleanQuery::apply($query, 'is_active');
            BooleanQuery::apply($query, 'is_archived', false);
            $query->where('starts_at', '>', $now);
        }

        if ($status === 'expired') {
            BooleanQuery::apply($query, 'is_active');
            BooleanQuery::apply($query, 'is_archived', false);
            $query->where('ends_at', '<', $now);
        }

        if ($status === 'inactive') {
            BooleanQuery::apply($query, 'is_active', false);
            BooleanQuery::apply($query, 'is_archived', false);
        }

        if ($status === 'archived') {
            BooleanQuery::apply($query, 'is_archived');
        }
    }
}
