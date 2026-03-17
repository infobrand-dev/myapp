<?php

namespace App\Modules\Payments\Services;

use Illuminate\Support\Facades\DB;

class PaymentNumberService
{
    public function nextNumber(?\DateTimeInterface $date = null): string
    {
        $date = $date ?: now();
        $sequenceDate = $date->format('Ymd');

        $sequence = DB::table('payment_sequences')
            ->where('sequence_date', $sequenceDate)
            ->lockForUpdate()
            ->first();

        if (!$sequence) {
            DB::table('payment_sequences')->insert([
                'sequence_date' => $sequenceDate,
                'last_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $number = 1;
        } else {
            $number = ((int) $sequence->last_number) + 1;

            DB::table('payment_sequences')
                ->where('id', $sequence->id)
                ->update([
                    'last_number' => $number,
                    'updated_at' => now(),
                ]);
        }

        return sprintf('PAY-%s-%05d', $sequenceDate, $number);
    }
}
