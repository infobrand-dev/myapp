<?php

namespace App\Modules\Wallet\Services;

use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use App\Modules\Wallet\Models\WalletLedgerEntry;
use App\Support\Commerce\CommerceOrderLifecycleService;
use Illuminate\Support\Collection;

class TenantWalletSettlementService
{
    public function __construct(
        private readonly TenantWalletService $wallets,
        private readonly CommerceOrderLifecycleService $commerceOrders,
    ) {
    }

    /**
     * @param  Collection<int, mixed>  $payables
     */
    public function handle(mixed $payment, Collection $payables): void
    {
        if (!$payment instanceof Payment) {
            return;
        }

        foreach ($payables as $payable) {
            if ($payable instanceof Sale) {
                $this->settleSale($payable);
            }

            if ($payable instanceof SaleReturn) {
                $this->settleRefund($payable);
            }
        }
    }

    private function settleSale(Sale $sale): void
    {
        $sale = $sale->fresh();
        if (!$sale || !$this->commerceOrders->isCommerceOrder($sale)) {
            return;
        }

        if (!in_array((string) $sale->payment_status, [Sale::PAYMENT_PAID, Sale::PAYMENT_OVERPAID], true)) {
            return;
        }

        if (data_get($sale->meta, 'commerce.wallet_settlement.settled_at')) {
            return;
        }

        $account = $this->wallets->account((int) $sale->tenant_id);
        $gross = round((float) $sale->grand_total, 2);
        $platformFee = round(
            ($gross * ((float) config('services.commerce_creator.platform_fee_percentage', 0) / 100))
            + (float) config('services.commerce_creator.platform_fee_flat', 0),
            2
        );
        $affiliateCommission = round((float) data_get($sale->meta, 'commerce.affiliate.commission_amount', 0), 2);

        $this->wallets->addEntry($account, [
            'source_type' => 'sale',
            'source_id' => (int) $sale->id,
            'entry_type' => 'gross_sale',
            'state' => 'available',
            'direction' => 'credit',
            'amount' => $gross,
            'notes' => 'Gross sale credited.',
        ]);

        if ($platformFee > 0) {
            $this->wallets->addEntry($account, [
                'source_type' => 'sale',
                'source_id' => (int) $sale->id,
                'entry_type' => 'platform_fee',
                'state' => 'available',
                'direction' => 'debit',
                'amount' => $platformFee,
                'notes' => 'Platform fee debited.',
            ]);
        }

        if ($affiliateCommission > 0) {
            $this->wallets->addEntry($account, [
                'source_type' => 'sale',
                'source_id' => (int) $sale->id,
                'entry_type' => 'affiliate_commission',
                'state' => 'locked',
                'direction' => 'debit',
                'amount' => $affiliateCommission,
                'notes' => 'Affiliate commission reserved.',
            ]);
        }

        $meta = is_array($sale->meta) ? $sale->meta : [];
        data_set($meta, 'commerce.wallet_settlement.status', 'available');
        data_set($meta, 'commerce.wallet_settlement.gross', $gross);
        data_set($meta, 'commerce.wallet_settlement.platform_fee', $platformFee);
        data_set($meta, 'commerce.wallet_settlement.affiliate_commission', $affiliateCommission);
        data_set($meta, 'commerce.wallet_settlement.settled_at', now()->toIso8601String());
        $sale->update(['meta' => $meta]);
    }

    private function settleRefund(SaleReturn $return): void
    {
        $return = $return->fresh('sale');
        if (!$return || !$return->sale || !$this->commerceOrders->isCommerceOrder($return->sale)) {
            return;
        }

        $account = $this->wallets->account((int) $return->tenant_id);
        $alreadyRecorded = WalletLedgerEntry::query()
            ->where('tenant_id', (int) $return->tenant_id)
            ->where('source_type', 'sale_return')
            ->where('source_id', (int) $return->id)
            ->exists();

        if ($alreadyRecorded) {
            return;
        }

        $this->wallets->addEntry($account, [
            'source_type' => 'sale_return',
            'source_id' => (int) $return->id,
            'entry_type' => 'refund_adjustment',
            'state' => 'available',
            'direction' => 'debit',
            'amount' => round((float) $return->grand_total, 2),
            'notes' => 'Refund adjustment for commerce order.',
        ]);
    }
}
