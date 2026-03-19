<?php

namespace App\Modules\Purchases\Services;

use Illuminate\Support\Facades\DB;

class PurchaseNumberService
{
    public function generate(?\DateTimeInterface $date = null): string
    {
        $date = $date ?: now();
        $sequenceDate = $date->format('Ymd');
        $prefix = 'PUR-' . $sequenceDate;

        $row = DB::table('purchase_sequences')
            ->where('sequence_date', $sequenceDate)
            ->lockForUpdate()
            ->first();

        if (!$row) {
            DB::table('purchase_sequences')->insert([
                'sequence_date' => $sequenceDate,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('purchase_sequences')
                ->where('sequence_date', $sequenceDate)
                ->lockForUpdate()
                ->first();
        }

        $nextSequence = ((int) $row->last_number) + 1;

        DB::table('purchase_sequences')
            ->where('id', $row->id)
            ->update([
                'last_number' => $nextSequence,
                'updated_at' => now(),
            ]);

        return sprintf('%s-%04d', $prefix, $nextSequence);
    }

    public function generateReceiptNumber(?\DateTimeInterface $date = null): string
    {
        $date = $date ?: now();
        $sequenceDate = $date->format('Ymd');
        $base = 'GRN-' . $sequenceDate;

        $row = DB::table('purchase_receipt_sequences')
            ->where('sequence_date', $sequenceDate)
            ->lockForUpdate()
            ->first();

        if (!$row) {
            DB::table('purchase_receipt_sequences')->insert([
                'sequence_date' => $sequenceDate,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('purchase_receipt_sequences')
                ->where('sequence_date', $sequenceDate)
                ->lockForUpdate()
                ->first();
        }

        $nextSequence = ((int) $row->last_number) + 1;

        DB::table('purchase_receipt_sequences')
            ->where('id', $row->id)
            ->update([
                'last_number' => $nextSequence,
                'updated_at' => now(),
            ]);

        return sprintf('%s-%04d', $base, $nextSequence);
    }
}
