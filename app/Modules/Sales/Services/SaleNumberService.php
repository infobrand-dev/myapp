<?php

namespace App\Modules\Sales\Services;

use Illuminate\Support\Facades\DB;

class SaleNumberService
{
    public function generate(?\DateTimeInterface $date = null): string
    {
        $date = $date ?: now();
        $sequenceDate = $date->format('Ymd');
        $prefix = 'SAL-' . $sequenceDate;

        $row = DB::table('sale_sequences')
            ->where('sequence_date', $sequenceDate)
            ->lockForUpdate()
            ->first();

        if (!$row) {
            DB::table('sale_sequences')->insert([
                'sequence_date' => $sequenceDate,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('sale_sequences')
                ->where('sequence_date', $sequenceDate)
                ->lockForUpdate()
                ->first();
        }

        $nextSequence = ((int) $row->last_number) + 1;

        DB::table('sale_sequences')
            ->where('id', $row->id)
            ->update([
                'last_number' => $nextSequence,
                'updated_at' => now(),
            ]);

        return sprintf('%s-%04d', $prefix, $nextSequence);
    }
}
