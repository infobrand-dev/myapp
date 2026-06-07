<?php

namespace App\Support;

use Illuminate\Http\Request;

class ModeAwarePayloadSanitizer
{
    public function sanitizeProduct(Request $request, ?object $product = null): array
    {
        if ($this->isAdvanced($request)) {
            return [];
        }

        $payload = [];

        if ($product && $product->type === 'variant' && !$request->filled('type')) {
            $payload['type'] = 'variant';
        }

        if (!$request->filled('minimum_stock')) {
            $payload['minimum_stock'] = $product?->minimum_stock ?? 0;
        }

        if (!$request->filled('reorder_point')) {
            $payload['reorder_point'] = $product?->reorder_point ?? 0;
        }

        return $payload;
    }

    public function sanitizeSale(Request $request, ?object $sale = null): array
    {
        if ($this->isAdvanced($request)) {
            return [];
        }

        $sourceContext = is_array(data_get($sale?->meta, 'source_context')) ? data_get($sale?->meta, 'source_context') : [];
        $taxContext = is_array(data_get($sale?->meta, 'tax')) ? data_get($sale?->meta, 'tax') : [];
        $totalsSnapshot = is_array($sale?->totals_snapshot) ? $sale->totals_snapshot : [];

        return [
            'source' => $request->input('source', data_get($sale, 'source', config('platform-core.commerce.sale_source_manual', 'manual'))),
            'external_reference' => $request->filled('external_reference') ? $request->input('external_reference') : $sale?->external_reference,
            'inventory_location_id' => $request->filled('inventory_location_id')
                ? (int) $request->input('inventory_location_id')
                : ($sourceContext['inventory_location_id'] ?? null),
            'tax_rate_id' => $request->filled('tax_rate_id')
                ? (int) $request->input('tax_rate_id')
                : ($taxContext['tax_rate_id'] ?? ($totalsSnapshot['tax_rate_id'] ?? null)),
            'header_discount_total' => $request->input('header_discount_total', $totalsSnapshot['header_discount_total'] ?? 0),
            'header_tax_total' => $request->input('header_tax_total', $totalsSnapshot['header_tax_total'] ?? 0),
        ];
    }

    public function sanitizePurchase(Request $request, ?object $purchase = null): array
    {
        if ($this->isAdvanced($request)) {
            return [];
        }

        $taxContext = is_array(data_get($purchase?->meta, 'tax')) ? data_get($purchase?->meta, 'tax') : [];

        return [
            'tax_rate_id' => $request->filled('tax_rate_id')
                ? (int) $request->input('tax_rate_id')
                : ($taxContext['tax_rate_id'] ?? null),
            'supplier_reference' => $request->input('supplier_reference', $purchase?->supplier_reference),
            'supplier_invoice_number' => $request->input('supplier_invoice_number', $purchase?->supplier_invoice_number),
            'supplier_bill_status' => $request->input('supplier_bill_status', data_get($purchase, 'supplier_bill_status', config('platform-core.purchases.bill_pending', 'pending'))),
            'supplier_bill_received_at' => $request->input('supplier_bill_received_at', $purchase?->supplier_bill_received_at),
            'supplier_notes' => $request->input('supplier_notes', $purchase?->supplier_notes),
            'internal_notes' => $request->input('internal_notes', $purchase?->internal_notes),
            'landed_cost_total' => $request->input('landed_cost_total', $purchase?->landed_cost_total ?? 0),
        ];
    }

    public function sanitizePayment(Request $request, ?object $payment = null): array
    {
        if ($this->isAdvanced($request)) {
            return [];
        }

        return [
            'source' => $request->input('source', data_get($payment, 'source', config('platform-core.payments.payment_source_backoffice', 'backoffice'))),
            'channel' => $request->input('channel', $payment?->channel),
            'reference_number' => $request->input('reference_number', $payment?->reference_number),
            'external_reference' => $request->input('external_reference', $payment?->external_reference),
            'reconciliation_status' => $request->input('reconciliation_status', data_get($payment, 'reconciliation_status', config('platform-core.payments.payment_reconciliation_unreconciled', 'unreconciled'))),
            'received_by' => $request->input('received_by', $payment?->received_by),
            'branch_id' => $request->has('branch_id')
                ? $request->input('branch_id')
                : $payment?->branch_id,
        ];
    }

    public function sanitizeFinanceTransaction(Request $request, ?object $transaction = null): array
    {
        if ($this->isAdvanced($request)) {
            return [];
        }

        return [
            'entry_mode' => $request->input('entry_mode', $transaction && method_exists($transaction, 'isTransfer') && $transaction->isTransfer()
                ? config('platform-core.finance.entry_mode_transfer', 'transfer')
                : config('platform-core.finance.entry_mode_standard', 'standard')),
            'counterparty_finance_account_id' => $request->input('counterparty_finance_account_id', $transaction?->counterparty_finance_account_id),
            'branch_id' => $request->has('branch_id') ? $request->input('branch_id') : $transaction?->branch_id,
            'pos_cash_session_id' => $request->has('pos_cash_session_id') ? $request->input('pos_cash_session_id') : $transaction?->pos_cash_session_id,
        ];
    }

    private function isAdvanced(Request $request): bool
    {
        return app(FeatureMode::class)->isAdvanced($request, 'accounting', $request->user());
    }
}
