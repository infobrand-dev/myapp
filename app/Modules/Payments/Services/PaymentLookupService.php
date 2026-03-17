<?php

namespace App\Modules\Payments\Services;

use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Collection;

class PaymentLookupService
{
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
        return PaymentMethod::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function receivers(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }

    public function payableTypeOptions(): array
    {
        return [
            'sale' => 'Sale',
        ];
    }

    public function saleOptions(): Collection
    {
        return Sale::query()
            ->where('status', Sale::STATUS_FINALIZED)
            ->whereNotIn('payment_status', [Sale::PAYMENT_PAID, Sale::PAYMENT_OVERPAID])
            ->orderByDesc('transaction_date')
            ->limit(100)
            ->get(['id', 'sale_number', 'customer_name_snapshot', 'grand_total', 'paid_total', 'balance_due']);
    }
}
