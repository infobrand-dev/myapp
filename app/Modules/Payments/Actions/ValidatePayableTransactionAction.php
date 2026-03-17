<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ValidatePayableTransactionAction
{
    public function execute(string $payableType, int $payableId): Model
    {
        $normalizedType = strtolower(trim($payableType));

        if ($normalizedType !== 'sale') {
            throw ValidationException::withMessages([
                'allocations' => 'Payable type tidak didukung oleh module Payments.',
            ]);
        }

        $sale = Sale::query()->find($payableId);
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
