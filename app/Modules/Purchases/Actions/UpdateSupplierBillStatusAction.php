<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Purchases\Models\Purchase;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSupplierBillStatusAction
{
    public function execute(Purchase $purchase, string $targetStatus, array $data, ?User $actor = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $targetStatus, $data, $actor) {
            $purchase = Purchase::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if (!$purchase->isConfirmedLike()) {
                throw ValidationException::withMessages([
                    'purchase' => 'Status tagihan supplier hanya bisa diubah untuk purchase yang sudah aktif.',
                ]);
            }

            $this->ensureValidTransition((string) $purchase->supplier_bill_status, $targetStatus);

            $currentBillStatus = (string) $purchase->supplier_bill_status;
            $receivedAt = $targetStatus === Purchase::BILL_PENDING
                ? null
                : ($data['supplier_bill_received_at'] ?? optional($purchase->supplier_bill_received_at)->toDateString() ?? now()->toDateString());

            $purchase->update([
                'supplier_bill_status' => $targetStatus,
                'supplier_bill_received_at' => $receivedAt,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? $purchase->supplier_invoice_number,
                'supplier_reference' => $data['supplier_reference'] ?? $purchase->supplier_reference,
                'updated_by' => $actor?->id,
            ]);

            $purchase->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $purchase->branch_id,
                'from_status' => $purchase->status,
                'to_status' => $purchase->status,
                'event' => 'supplier_bill_status_updated',
                'reason' => $data['reason'] ?? null,
                'actor_id' => $actor?->id,
                'meta' => [
                    'from_bill_status' => $currentBillStatus,
                    'to_bill_status' => $targetStatus,
                    'supplier_bill_received_at' => $receivedAt,
                    'supplier_invoice_number' => $purchase->supplier_invoice_number,
                    'supplier_reference' => $purchase->supplier_reference,
                ],
            ]);

            return $purchase->fresh();
        });
    }

    private function ensureValidTransition(string $currentStatus, string $targetStatus): void
    {
        if ($currentStatus === $targetStatus) {
            throw ValidationException::withMessages([
                'supplier_bill_status' => 'Status tagihan supplier sudah sama dengan pilihan yang diminta.',
            ]);
        }

        $allowedTransitions = [
            Purchase::BILL_PENDING => [Purchase::BILL_RECEIVED],
            Purchase::BILL_RECEIVED => [Purchase::BILL_PENDING, Purchase::BILL_VERIFIED],
            Purchase::BILL_VERIFIED => [Purchase::BILL_RECEIVED],
        ];

        if (!in_array($targetStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
            throw ValidationException::withMessages([
                'supplier_bill_status' => 'Transisi status tagihan supplier tidak valid dari status saat ini.',
            ]);
        }
    }
}
