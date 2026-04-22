<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Purchases\Events\PurchaseFinalized;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\Services\PurchaseSnapshotService;
use App\Modules\Finance\Services\TaxDocumentSyncService;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\DocumentWorkflowService;
use App\Support\SensitiveActionApprovalService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizePurchaseAction
{
    private $syncPaymentSummary;
    private $snapshotService;
    private $journalService;
    private $periodLockService;
    private $documentWorkflow;
    private $approvalService;
    private $taxDocumentSyncService;

    public function __construct(
        SyncPurchasePaymentSummaryAction $syncPaymentSummary,
        PurchaseSnapshotService $snapshotService,
        AccountingJournalService $journalService,
        AccountingPeriodLockService $periodLockService,
        DocumentWorkflowService $documentWorkflow,
        SensitiveActionApprovalService $approvalService,
        TaxDocumentSyncService $taxDocumentSyncService
    )
    {
        $this->syncPaymentSummary = $syncPaymentSummary;
        $this->snapshotService = $snapshotService;
        $this->journalService = $journalService;
        $this->periodLockService = $periodLockService;
        $this->documentWorkflow = $documentWorkflow;
        $this->approvalService = $approvalService;
        $this->taxDocumentSyncService = $taxDocumentSyncService;
    }

    public function execute(Purchase $purchase, array $data, ?User $actor = null): Purchase
    {
        $purchase = DB::transaction(function () use ($purchase, $data, $actor) {
            $purchase = Purchase::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if ($purchase->confirmed_at) {
                return $this->syncPaymentSummary->execute($purchase)->load('items');
            }

            if (!$purchase->isDraft()) {
                throw ValidationException::withMessages([
                    'purchase' => 'Hanya draft purchase yang dapat di-finalize.',
                ]);
            }

            if ($this->documentWorkflow->requiresApprovalBeforeFinalize('purchase', $purchase->company_id, $purchase->branch_id)) {
                $this->approvalService->ensureApprovedOrCreatePending(
                    'purchases',
                    'finalize-purchase',
                    $purchase,
                    [
                        'purchase_number' => $purchase->purchase_number,
                        'purchase_date' => optional($purchase->purchase_date)->toDateTimeString(),
                        'grand_total' => (float) $purchase->grand_total,
                        'payment_status' => $purchase->payment_status,
                    ],
                    $actor,
                    'Finalize purchase memerlukan approval sesuai workflow dokumen.'
                );
            }

            $this->periodLockService->ensureDateOpen($purchase->purchase_date ?: now(), $purchase->branch_id, 'finalize purchase');

            if ($purchase->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Purchase harus memiliki item sebelum di-finalize.',
                ]);
            }

            $supplier = $purchase->contact_id
                ? ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($purchase->contact_id)
                : null;
            $supplierSnapshot = $this->snapshotService->supplierSnapshot($supplier);

            $fromStatus = $purchase->status;
            $purchase->update([
                'contact_id' => $supplier ? $supplier->id : null,
                'supplier_name_snapshot' => $supplierSnapshot['name'],
                'supplier_email_snapshot' => $supplierSnapshot['email'],
                'supplier_phone_snapshot' => $supplierSnapshot['phone'],
                'supplier_address_snapshot' => $supplierSnapshot['address'],
                'supplier_snapshot' => $supplierSnapshot['payload'],
                'status' => Purchase::STATUS_CONFIRMED,
                'purchase_date' => $data['purchase_date'] ?? $purchase->purchase_date ?? now(),
                'due_date' => array_key_exists('due_date', $data) ? ($data['due_date'] ?? null) : $purchase->due_date,
                'confirmed_at' => now(),
                'confirmed_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
                'notes' => $data['notes'] ?? $purchase->notes,
                'internal_notes' => $data['internal_notes'] ?? $purchase->internal_notes,
                'totals_snapshot' => array_merge($purchase->totals_snapshot ?? [], [
                    'finalized_at' => now()->toDateTimeString(),
                ]),
            ]);

            foreach ($purchase->items as $item) {
                if (!$item->product) {
                    continue;
                }

                $productSnapshot = $this->snapshotService->productSnapshot($item->product, $item->variant);
                $item->update([
                    'product_name_snapshot' => $productSnapshot['product_name'],
                    'variant_name_snapshot' => $productSnapshot['variant_name'],
                    'sku_snapshot' => $productSnapshot['sku'],
                    'unit_snapshot' => $productSnapshot['unit'],
                    'product_snapshot' => $productSnapshot['payload'],
                ]);
            }

            $purchase->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $purchase->branch_id,
                'from_status' => $fromStatus,
                'to_status' => Purchase::STATUS_CONFIRMED,
                'event' => 'finalized',
                'reason' => $data['reason'] ?? null,
                'actor_id' => $actor ? $actor->id : null,
                'meta' => [
                    'purchase_number' => $purchase->purchase_number,
                    'subtotal' => (float) $purchase->subtotal,
                    'discount_total' => (float) $purchase->discount_total,
                    'tax_total' => (float) $purchase->tax_total,
                    'landed_cost_total' => (float) $purchase->landed_cost_total,
                    'grand_total' => (float) $purchase->grand_total,
                    'supplier_bill_status' => $purchase->supplier_bill_status,
                ],
            ]);

            $purchase = $this->syncPaymentSummary->execute($purchase)->load('items');

            $this->journalService->sync(
                $purchase,
                'purchase_finalized',
                $purchase->purchase_date ?: now(),
                $this->journalLines($purchase),
                [
                    'payment_status' => $purchase->payment_status,
                    'grand_total' => (float) $purchase->grand_total,
                ],
                'Auto journal purchase ' . $purchase->purchase_number
            );

            $this->taxDocumentSyncService->syncFromSource($purchase, $actor);

            return $purchase;
        });

        event(new PurchaseFinalized($purchase));

        return $purchase;
    }

    private function journalLines(Purchase $purchase): array
    {
        $lines = [
            [
                'account_code' => 'PURCHASES',
                'account_name' => 'Purchases / Inventory',
                'debit' => (float) $purchase->subtotal,
                'credit' => 0,
            ],
            [
                'account_code' => 'AP',
                'account_name' => 'Accounts Payable',
                'debit' => 0,
                'credit' => (float) $purchase->grand_total,
            ],
        ];

        if ((float) $purchase->tax_total > 0) {
            $lines[] = [
                'account_code' => 'PURCHASE_TAX',
                'account_name' => 'Purchase Tax',
                'debit' => (float) $purchase->tax_total,
                'credit' => 0,
            ];
        }

        if ((float) $purchase->landed_cost_total > 0) {
            $lines[] = [
                'account_code' => 'LANDED_COST',
                'account_name' => 'Landed Cost',
                'debit' => (float) $purchase->landed_cost_total,
                'credit' => 0,
            ];
        }

        if ((float) $purchase->discount_total > 0) {
            $lines[] = [
                'account_code' => 'PURCHASE_DISC',
                'account_name' => 'Purchase Discount',
                'debit' => 0,
                'credit' => (float) $purchase->discount_total,
            ];
        }

        return $lines;
    }
}
