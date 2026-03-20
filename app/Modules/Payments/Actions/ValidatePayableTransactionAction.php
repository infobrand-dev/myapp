<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Purchases\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ValidatePayableTransactionAction
{

    public function execute(string $payableType, int $payableId): Model
    {
        $normalizedType = strtolower(trim($payableType));

        if ($normalizedType === 'sale_return') {
            $saleReturn = SaleReturn::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->find($payableId);
            if (!$saleReturn) {
                throw ValidationException::withMessages([
                    'allocations' => 'Transaksi sales return tidak ditemukan.',
                ]);
            }

            if (!$saleReturn->isFinalized()) {
                throw ValidationException::withMessages([
                    'allocations' => 'Refund hanya bisa dicatat untuk sales return yang sudah finalized.',
                ]);
            }

            if (!$saleReturn->refund_required) {
                throw ValidationException::withMessages([
                    'allocations' => 'Sales return ini tidak membutuhkan refund.',
                ]);
            }

            return $saleReturn;
        }

        if ($normalizedType === 'purchase') {
            $purchase = Purchase::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->find($payableId);
            if (!$purchase) {
                throw ValidationException::withMessages([
                    'allocations' => 'Transaksi purchase tidak ditemukan.',
                ]);
            }

            if (!in_array($purchase->status, [Purchase::STATUS_CONFIRMED, Purchase::STATUS_PARTIAL_RECEIVED, Purchase::STATUS_RECEIVED], true)) {
                throw ValidationException::withMessages([
                    'allocations' => 'Pembayaran supplier hanya bisa dicatat untuk purchase yang sudah confirmed/received.',
                ]);
            }

            if (in_array($purchase->status, [Purchase::STATUS_VOIDED, Purchase::STATUS_CANCELLED], true)) {
                throw ValidationException::withMessages([
                    'allocations' => 'Pembayaran tidak dapat dicatat untuk purchase void/cancelled.',
                ]);
            }

            return $purchase;
        }

        if ($normalizedType !== 'sale') {
            throw ValidationException::withMessages([
                'allocations' => 'Payable type tidak didukung oleh module Payments.',
            ]);
        }

        $sale = Sale::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->find($payableId);
        if (!$sale) {
            throw ValidationException::withMessages([
                'allocations' => 'Transaksi sale tidak ditemukan.',
            ]);
        }

        if (!$sale->isFinalized()) {
            throw ValidationException::withMessages([
                'allocations' => 'Pembayaran hanya bisa dibuat untuk sale yang sudah finalized.',
            ]);
        }

        if (in_array($sale->status, [Sale::STATUS_VOIDED, Sale::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages([
                'allocations' => 'Pembayaran tidak dapat dicatat untuk sale void/cancelled.',
            ]);
        }

        return $sale;
    }
}
