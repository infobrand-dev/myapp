<?php

namespace App\Modules\Payments\Services;

use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use App\Support\BooleanQuery;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class PaymentLookupService
{
    private const MAX_LOOKUP_ROWS = 100;

    public function paymentStatusOptions(): array
    {
        return [
            Payment::STATUS_PENDING => 'Pending',
            Payment::STATUS_POSTED => 'Posted',
            Payment::STATUS_VOIDED => 'Voided',
            Payment::STATUS_CANCELLED => 'Cancelled',
            Payment::STATUS_REFUNDED => 'Refunded',
        ];
    }

    public function reconciliationStatusOptions(): array
    {
        return [
            Payment::RECONCILIATION_UNRECONCILED => 'Unreconciled',
            Payment::RECONCILIATION_IN_REVIEW => 'In Review',
            Payment::RECONCILIATION_RECONCILED => 'Reconciled',
        ];
    }

    public function paymentSourceOptions(): array
    {
        return [
            Payment::SOURCE_BACKOFFICE => 'Backoffice',
            Payment::SOURCE_POS => 'POS',
            Payment::SOURCE_API => 'API',
            Payment::SOURCE_ONLINE => 'Online',
            Payment::SOURCE_MANUAL => 'Manual',
        ];
    }

    public function paymentMethodTypeOptions(): array
    {
        return [
            PaymentMethod::TYPE_CASH => 'Cash',
            PaymentMethod::TYPE_BANK_TRANSFER => 'Bank Transfer',
            PaymentMethod::TYPE_DEBIT_CARD => 'Debit Card',
            PaymentMethod::TYPE_CREDIT_CARD => 'Credit Card',
            PaymentMethod::TYPE_EWALLET => 'E-Wallet',
            PaymentMethod::TYPE_QRIS => 'QRIS',
            PaymentMethod::TYPE_MANUAL => 'Custom / Manual',
        ];
    }

    public function paymentMethods(bool $activeOnly = true): Collection
    {
        $query = PaymentMethod::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($activeOnly) {
            BooleanQuery::apply($query, 'is_active');
        }

        return $query->get();
    }

    public function receivers(): Collection
    {
        return User::query()
            ->where('tenant_id', TenantContext::currentId())
            ->select(['id', 'name'])
            ->orderBy('name')
            ->limit(self::MAX_LOOKUP_ROWS)
            ->get();
    }

    public function payableTypeOptions(): array
    {
        $options = [
            'sale' => 'Sale',
            'sale_return' => 'Sales Return Refund',
        ];

        if ($this->purchaseModuleReady()) {
            $options['purchase'] = 'Purchase';
        }

        return $options;
    }

    public function saleOptions(?int $includePaymentId = null): Collection
    {
        $query = Sale::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->where('status', Sale::STATUS_FINALIZED)
            ->orderByDesc('transaction_date')
            ->limit(self::MAX_LOOKUP_ROWS);

        if ($includePaymentId === null) {
            $query->whereNotIn('payment_status', [Sale::PAYMENT_PAID, Sale::PAYMENT_OVERPAID]);
        }

        return $query->get(['id', 'sale_number', 'customer_name_snapshot', 'grand_total', 'paid_total', 'balance_due', 'branch_id']);
    }

    public function saleReturnOptions(?int $includePaymentId = null): Collection
    {
        $query = SaleReturn::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->where('status', SaleReturn::STATUS_FINALIZED)
            ->refundRequired()
            ->orderByDesc('return_date')
            ->limit(self::MAX_LOOKUP_ROWS);

        if ($includePaymentId === null) {
            $query->whereNotIn('refund_status', [SaleReturn::REFUND_REFUNDED, SaleReturn::REFUND_SKIPPED]);
        }

        return $query->get(['id', 'return_number', 'sale_number_snapshot', 'customer_name_snapshot', 'grand_total', 'refunded_total', 'refund_balance', 'branch_id']);
    }

    public function purchaseOptions(?int $includePaymentId = null): Collection
    {
        if (!$this->purchaseModuleReady()) {
            return collect();
        }

        $query = Purchase::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->whereIn('status', [Purchase::STATUS_CONFIRMED, Purchase::STATUS_PARTIAL_RECEIVED, Purchase::STATUS_RECEIVED])
            ->orderByDesc('purchase_date')
            ->limit(self::MAX_LOOKUP_ROWS);

        if ($includePaymentId === null) {
            $query->whereNotIn('payment_status', [Purchase::PAYMENT_PAID, Purchase::PAYMENT_OVERPAID]);
        }

        return $query->get(['id', 'purchase_number', 'supplier_name_snapshot', 'grand_total', 'paid_total', 'balance_due', 'branch_id']);
    }

    private function purchaseModuleReady(): bool
    {
        return class_exists(Purchase::class) && Schema::hasTable('purchases');
    }
}
