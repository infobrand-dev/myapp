<?php

namespace App\Support;

use App\Models\ApprovalMatrixRule;
use Illuminate\Support\Carbon;

class ApprovalMatrixService
{
    public function applicableRule(string $module, string $action, ?int $branchId, float $amount): ?ApprovalMatrixRule
    {
        $query = ApprovalMatrixRule::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('module', $module)
            ->where('action', $action)
            ->where('min_amount', '<=', $amount)
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id');

                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->orderByRaw('CASE WHEN branch_id IS NULL THEN 1 ELSE 0 END ASC')
            ->orderByDesc('min_amount')
            ->latest('id');

        return BooleanQuery::apply($query, 'is_active', true)->first();
    }

    public function moduleActionOptions(): array
    {
        return [
            'finance' => ['update_transaction', 'delete_transaction', 'post_manual_journal', 'reverse_journal', 'reopen_period_closing'],
            'sales' => ['finalize-sale', 'void_sale'],
            'purchases' => ['finalize-purchase', 'void_purchase'],
            'payments' => ['update_payment', 'void_payment'],
        ];
    }

    public function exceedsBackdateWindow(?ApprovalMatrixRule $rule, $actionDate): bool
    {
        if (!$rule || $rule->max_backdate_days === null || !$actionDate) {
            return false;
        }

        $resolvedDate = Carbon::parse($actionDate)->startOfDay();
        $minimumDate = now()->startOfDay()->subDays((int) $rule->max_backdate_days);

        return $resolvedDate->lt($minimumDate);
    }
}
