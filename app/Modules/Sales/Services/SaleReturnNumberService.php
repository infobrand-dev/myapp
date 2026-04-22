<?php

namespace App\Modules\Sales\Services;

use App\Support\DocumentNumberingService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class SaleReturnNumberService
{
    private $documentNumbering;

    public function __construct(DocumentNumberingService $documentNumbering)
    {
        $this->documentNumbering = $documentNumbering;
    }

    public function generate(?\DateTimeInterface $date = null): string
    {
        $date = $date ?: now();

        $configuredNumber = $this->documentNumbering->nextConfiguredNumber('sale_return', $date);

        if ($configuredNumber) {
            return $configuredNumber;
        }

        $sequenceDate = $date->format('Ymd');
        $prefix = 'RET-' . $sequenceDate;

        $row = DB::table('sale_return_sequences')
            ->where('tenant_id', TenantContext::currentId())
            ->where('sequence_date', $sequenceDate)
            ->lockForUpdate()
            ->first();

        if (!$row) {
            DB::table('sale_return_sequences')->insert([
                'tenant_id' => TenantContext::currentId(),
                'sequence_date' => $sequenceDate,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('sale_return_sequences')
                ->where('tenant_id', TenantContext::currentId())
                ->where('sequence_date', $sequenceDate)
                ->lockForUpdate()
                ->first();
        }

        $nextSequence = ((int) $row->last_number) + 1;

        DB::table('sale_return_sequences')
            ->where('id', $row->id)
            ->update([
                'last_number' => $nextSequence,
                'updated_at' => now(),
            ]);

        return sprintf('%s-%04d', $prefix, $nextSequence);
    }
}
