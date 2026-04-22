<?php

namespace App\Modules\Payments\Services;

use App\Support\DocumentNumberingService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class PaymentNumberService
{
    private $documentNumbering;

    public function __construct(DocumentNumberingService $documentNumbering)
    {
        $this->documentNumbering = $documentNumbering;
    }

    public function nextNumber(?\DateTimeInterface $date = null): string
    {
        $date = $date ?: now();

        $configuredNumber = $this->documentNumbering->nextConfiguredNumber('payment', $date);

        if ($configuredNumber) {
            return $configuredNumber;
        }

        $sequenceDate = $date->format('Ymd');

        $sequence = DB::table('payment_sequences')
            ->where('tenant_id', TenantContext::currentId())
            ->where('sequence_date', $sequenceDate)
            ->lockForUpdate()
            ->first();

        if (!$sequence) {
            DB::table('payment_sequences')->insert([
                'tenant_id' => TenantContext::currentId(),
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
