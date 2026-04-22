<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\Services\PurchaseSnapshotService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateDraftPurchaseAction
{
    private $recalculateTotals;
    private $snapshotService;
    private $syncPaymentSummary;

    public function __construct(
        RecalculatePurchaseTotalsAction $recalculateTotals,
        PurchaseSnapshotService $snapshotService,
        SyncPurchasePaymentSummaryAction $syncPaymentSummary
    ) {
        $this->recalculateTotals = $recalculateTotals;
        $this->snapshotService = $snapshotService;
        $this->syncPaymentSummary = $syncPaymentSummary;
    }

    public function execute(Purchase $purchase, array $data, ?User $actor = null): Purchase
    {
        if (!$purchase->isDraft()) {
            throw ValidationException::withMessages([
                'purchase' => 'Hanya draft purchase yang boleh diedit.',
            ]);
        }

        return DB::transaction(function () use ($purchase, $data, $actor) {
            $purchase = Purchase::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($purchase->id);
            $resolvedBranchId = $purchase->branch_id ?: BranchContext::currentOrDefaultId($actor, CompanyContext::currentId());
            $totals = $this->recalculateTotals->execute($data);
            $supplier = ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($data['contact_id']);
            $snapshot = $this->snapshotService->supplierSnapshot($supplier);
            $meta = $purchase->meta ?? [];
            $meta['tax'] = $this->taxContext($totals);

            $purchase->update([
                'contact_id' => $supplier ? $supplier->id : null,
                'supplier_name_snapshot' => $snapshot['name'],
                'supplier_email_snapshot' => $snapshot['email'],
                'supplier_phone_snapshot' => $snapshot['phone'],
                'supplier_address_snapshot' => $snapshot['address'],
                'supplier_snapshot' => $snapshot['payload'],
                'supplier_reference' => array_key_exists('supplier_reference', $data) ? ($data['supplier_reference'] ?? null) : $purchase->supplier_reference,
                'supplier_invoice_number' => array_key_exists('supplier_invoice_number', $data) ? ($data['supplier_invoice_number'] ?? null) : $purchase->supplier_invoice_number,
                'supplier_notes' => array_key_exists('supplier_notes', $data) ? ($data['supplier_notes'] ?? null) : $purchase->supplier_notes,
                'supplier_bill_status' => $data['supplier_bill_status'] ?? $purchase->supplier_bill_status,
                'purchase_date' => $data['purchase_date'],
                'due_date' => array_key_exists('due_date', $data) ? ($data['due_date'] ?? null) : $purchase->due_date,
                'expected_receive_date' => array_key_exists('expected_receive_date', $data) ? ($data['expected_receive_date'] ?? null) : $purchase->expected_receive_date,
                'supplier_bill_received_at' => array_key_exists('supplier_bill_received_at', $data) ? ($data['supplier_bill_received_at'] ?? null) : $purchase->supplier_bill_received_at,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'landed_cost_total' => $totals['landed_cost_total'],
                'grand_total' => $totals['grand_total'],
                'balance_due' => $totals['grand_total'],
                'currency_code' => $data['currency_code'] ?? $purchase->currency_code,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => array_key_exists('internal_notes', $data) ? ($data['internal_notes'] ?? null) : $purchase->internal_notes,
                'totals_snapshot' => $totals['totals_snapshot'],
                'meta' => $meta,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $purchase->items()->delete();
            $purchase->items()->createMany($this->withTenantId($totals['items'], $resolvedBranchId));

            return $this->syncPaymentSummary->execute($purchase);
        });
    }

    private function withTenantId(array $rows, ?int $branchId): array
    {
        return array_map(function (array $row) use ($branchId): array {
            $row['tenant_id'] = TenantContext::currentId();
            $row['company_id'] = CompanyContext::currentId();
            $row['branch_id'] = $branchId;

            return $row;
        }, $rows);
    }

    private function taxContext(array $totals): ?array
    {
        $context = data_get($totals, 'tax_context');

        if (!is_array($context)) {
            return null;
        }

        if (empty($context['tax_rate_id']) && empty($context['tax_snapshot'])) {
            return null;
        }

        return $context;
    }
}
