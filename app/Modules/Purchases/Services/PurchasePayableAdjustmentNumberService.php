<?php

namespace App\Modules\Purchases\Services;

use App\Modules\Purchases\Models\PurchasePayableAdjustment;
use App\Support\DocumentNumberingService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class PurchasePayableAdjustmentNumberService
{
    public function __construct(
        private readonly DocumentNumberingService $documentNumbering,
    ) {
    }

    public function generate(string $type, ?\DateTimeInterface $date = null): string
    {
        $date = $date ?: now();
        $documentType = $type === PurchasePayableAdjustment::TYPE_WRITE_OFF ? 'write_off_note' : 'debit_note';
        $configuredNumber = $this->documentNumbering->nextConfiguredNumber($documentType, $date);

        if ($configuredNumber) {
            return $configuredNumber;
        }

        $sequenceDate = $date->format('Ymd');
        $prefix = $type === PurchasePayableAdjustment::TYPE_WRITE_OFF ? 'WOF-' . $sequenceDate : 'DNT-' . $sequenceDate;

        $row = DB::table('purchase_payable_adjustment_sequences')
            ->where('tenant_id', TenantContext::currentId())
            ->where('sequence_date', $sequenceDate)
            ->where('adjustment_type', $type)
            ->lockForUpdate()
            ->first();

        if (!$row) {
            DB::table('purchase_payable_adjustment_sequences')->insert([
                'tenant_id' => TenantContext::currentId(),
                'sequence_date' => $sequenceDate,
                'adjustment_type' => $type,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('purchase_payable_adjustment_sequences')
                ->where('tenant_id', TenantContext::currentId())
                ->where('sequence_date', $sequenceDate)
                ->where('adjustment_type', $type)
                ->lockForUpdate()
                ->first();
        }

        $nextSequence = ((int) $row->last_number) + 1;

        DB::table('purchase_payable_adjustment_sequences')
            ->where('id', $row->id)
            ->update([
                'last_number' => $nextSequence,
                'updated_at' => now(),
            ]);

        return sprintf('%s-%04d', $prefix, $nextSequence);
    }
}
