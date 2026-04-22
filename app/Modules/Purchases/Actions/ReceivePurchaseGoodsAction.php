<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Inventory\Services\StockMutationService;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\Services\PurchaseNumberService;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivePurchaseGoodsAction
{
    private $numberService;
    private $syncPaymentSummary;
    private $periodLockService;
    private $journalService;

    public function __construct(
        PurchaseNumberService $numberService,
        SyncPurchasePaymentSummaryAction $syncPaymentSummary,
        AccountingPeriodLockService $periodLockService,
        AccountingJournalService $journalService
    ) {
        $this->numberService = $numberService;
        $this->syncPaymentSummary = $syncPaymentSummary;
        $this->periodLockService = $periodLockService;
        $this->journalService = $journalService;
    }

    public function execute(Purchase $purchase, array $data, ?User $actor = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $data, $actor) {
            $purchase = Purchase::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if (!$purchase->isConfirmedLike()) {
                throw ValidationException::withMessages([
                    'purchase' => 'Receiving hanya dapat dilakukan untuk purchase yang sudah confirmed/partial received.',
                ]);
            }

            $this->periodLockService->ensureDateOpen(
                $data['receipt_date'] ?? now(),
                $purchase->branch_id,
                'purchase receiving'
            );

            $receiptRows = collect($data['items'] ?? [])
                ->filter(fn ($row) => is_array($row) && (float) ($row['qty_received'] ?? 0) > 0)
                ->values();

            if ($receiptRows->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Minimal satu item receiving wajib diisi.',
                ]);
            }

            $normalizedReceiptLines = $receiptRows
                ->map(fn ($row) => [
                    'purchase_item_id' => (int) $row['purchase_item_id'],
                    'qty_received' => round((float) $row['qty_received'], 4),
                ])
                ->sortBy('purchase_item_id')
                ->values()
                ->all();

            $receiptFingerprint = sha1(json_encode([
                'purchase_id' => $purchase->id,
                'inventory_location_id' => (int) $data['inventory_location_id'],
                'receipt_date' => (string) ($data['receipt_date'] ?? ''),
                'items' => $normalizedReceiptLines,
            ]));

            $existingReceipt = $purchase->receipts()
                ->where('fingerprint', $receiptFingerprint)
                ->first();

            if ($existingReceipt) {
                return $this->syncPaymentSummary->execute($purchase->fresh())->load('items', 'receipts.items');
            }

            /** @var StockMutationService $stockMutation */
            $stockMutation = app(StockMutationService::class);

            $receipt = $purchase->receipts()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $purchase->branch_id,
                'receipt_number' => $this->numberService->generateReceiptNumber(),
                'inventory_location_id' => $data['inventory_location_id'],
                'fingerprint' => $receiptFingerprint,
                'status' => 'posted',
                'receipt_date' => $data['receipt_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'total_received_qty' => 0,
                'meta' => null,
                'received_by' => $actor ? $actor->id : null,
                'created_by' => $actor ? $actor->id : null,
            ]);

            $totalReceivedQty = 0;
            $totalInventoryValue = 0;
            foreach ($receiptRows as $index => $row) {
                $purchaseItem = $purchase->items->firstWhere('id', (int) ($row['purchase_item_id'] ?? 0));
                if (!$purchaseItem) {
                    throw ValidationException::withMessages([
                        "items.{$index}.purchase_item_id" => 'Item purchase tidak ditemukan.',
                    ]);
                }

                $receiveQty = round((float) $row['qty_received'], 4);
                $remainingQty = $purchaseItem->remainingQty();
                if ($receiveQty > $remainingQty) {
                    throw ValidationException::withMessages([
                        "items.{$index}.qty_received" => 'Receiving tidak boleh melebihi sisa quantity purchase.',
                    ]);
                }

                $movement = $stockMutation->record([
                    'product_id' => $purchaseItem->product_id,
                    'product_variant_id' => $purchaseItem->product_variant_id,
                    'inventory_location_id' => $data['inventory_location_id'],
                    'movement_type' => 'purchase_receipt',
                    'direction' => 'in',
                    'quantity' => $receiveQty,
                    'unit_cost' => (float) $purchaseItem->unit_cost,
                    'reference_type' => $receipt->getMorphClass(),
                    'reference_id' => $receipt->getKey(),
                    'reason_code' => 'purchase_receipt',
                    'reason_text' => $data['notes'] ?? ('Receipt ' . $receipt->receipt_number),
                    'occurred_at' => $data['receipt_date'] ?? now(),
                    'performed_by' => $actor ? $actor->id : null,
                    'approved_by' => $actor ? $actor->id : null,
                    'meta' => [
                        'purchase_id' => $purchase->id,
                        'purchase_number' => $purchase->purchase_number,
                        'purchase_item_id' => $purchaseItem->id,
                        'unit_cost' => (float) $purchaseItem->unit_cost,
                    ],
                ]);

                $purchaseItem->update([
                    'qty_received' => round((float) $purchaseItem->qty_received + $receiveQty, 4),
                ]);

                $receipt->items()->create([
                    'tenant_id' => TenantContext::currentId(),
                    'company_id' => CompanyContext::currentId(),
                    'branch_id' => $purchase->branch_id,
                    'purchase_item_id' => $purchaseItem->id,
                    'product_id' => $purchaseItem->product_id,
                    'product_variant_id' => $purchaseItem->product_variant_id,
                    'qty_received' => $receiveQty,
                    'inventory_snapshot' => [
                        'inventory_stock_id' => $movement->inventory_stock_id,
                        'movement_id' => $movement->id,
                        'inventory_location_id' => $movement->inventory_location_id,
                        'occurred_at' => optional($movement->occurred_at)->toDateTimeString(),
                    ],
                ]);

                $totalReceivedQty += $receiveQty;
                $totalInventoryValue += (float) ($movement->movement_value ?? ($receiveQty * (float) $purchaseItem->unit_cost));
            }

            $freshItems = $purchase->items()->get();
            $orderedQty = round((float) $freshItems->sum('qty'), 4);
            $receivedQty = round((float) $freshItems->sum('qty_received'), 4);
            $nextStatus = $receivedQty >= $orderedQty
                ? Purchase::STATUS_RECEIVED
                : Purchase::STATUS_PARTIAL_RECEIVED;

            $fromStatus = $purchase->status;
            $receipt->update([
                'total_received_qty' => $totalReceivedQty,
                'integration_snapshot' => [
                    'inventory' => [
                        'status' => 'completed',
                        'location_id' => (int) $data['inventory_location_id'],
                        'processed_at' => now()->toDateTimeString(),
                    ],
                ],
            ]);

            $this->syncInventoryJournal($purchase, $receipt, $totalInventoryValue);

            $purchase->update([
                'status' => $nextStatus,
                'received_total_qty' => $receivedQty,
                'integration_snapshot' => array_merge($purchase->integration_snapshot ?? [], [
                    'last_receipt_number' => $receipt->receipt_number,
                    'last_receipt_at' => now()->toDateTimeString(),
                ]),
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $purchase->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $purchase->branch_id,
                'from_status' => $fromStatus,
                'to_status' => $nextStatus,
                'event' => 'received',
                'reason' => $data['notes'] ?? null,
                'actor_id' => $actor ? $actor->id : null,
                'meta' => [
                    'receipt_number' => $receipt->receipt_number,
                    'received_qty' => $totalReceivedQty,
                ],
            ]);

            return $this->syncPaymentSummary->execute($purchase)->load('items', 'receipts.items');
        });
    }

    private function syncInventoryJournal(Purchase $purchase, $receipt, float $inventoryValue): void
    {
        $inventoryValue = round($inventoryValue, 2);

        if ($inventoryValue <= 0) {
            return;
        }

        $this->journalService->sync(
            $receipt,
            'purchase_receipt_inventory',
            $receipt->receipt_date ?: now(),
            [
                [
                    'account_code' => 'INVENTORY',
                    'account_name' => 'Inventory',
                    'debit' => $inventoryValue,
                    'credit' => 0,
                ],
                [
                    'account_code' => 'PURCHASES',
                    'account_name' => 'Purchases / Inventory',
                    'debit' => 0,
                    'credit' => $inventoryValue,
                ],
            ],
            [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'receipt_number' => $receipt->receipt_number,
                'inventory_location_id' => $receipt->inventory_location_id,
                'inventory_value' => $inventoryValue,
                'received_qty' => (float) $receipt->total_received_qty,
            ],
            'Auto journal purchase receipt ' . $receipt->receipt_number
        );
    }
}
