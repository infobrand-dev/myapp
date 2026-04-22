<?php

namespace App\Modules\Sales\Services;

use App\Support\DocumentNumberingService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class SaleNumberService
{
    private $documentNumbering;

    public function __construct(DocumentNumberingService $documentNumbering)
    {
        $this->documentNumbering = $documentNumbering;
    }

    public function generate(?\DateTimeInterface $date = null, ?int $companyId = null, ?int $branchId = null): string
    {
        $date = $date ?: now();

        $configuredNumber = $this->documentNumbering->nextConfiguredNumber('sale', $date, $companyId, $branchId);

        if ($configuredNumber) {
            return $configuredNumber;
        }

        return $this->nextSequenceFallback($date);
    }

    private function nextSequenceFallback(\DateTimeInterface $date): string
    {
        $sequenceDate = $date->format('Ymd');
        $prefix = 'SAL-' . $sequenceDate;

        $row = DB::table('sale_sequences')
            ->where('tenant_id', TenantContext::currentId())
            ->where('sequence_date', $sequenceDate)
            ->lockForUpdate()
            ->first();

        if (!$row) {
            DB::table('sale_sequences')->insert([
                'tenant_id' => TenantContext::currentId(),
                'sequence_date' => $sequenceDate,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('sale_sequences')
                ->where('tenant_id', TenantContext::currentId())
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
