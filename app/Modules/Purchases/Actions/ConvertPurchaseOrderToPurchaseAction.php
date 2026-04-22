<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Purchases\Models\PurchaseOrder;
use App\Support\BranchContext;
use App\Support\DocumentWorkflowService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertPurchaseOrderToPurchaseAction
{
    private $createDraftPurchase;
    private $documentWorkflow;

    public function __construct(CreateDraftPurchaseAction $createDraftPurchase, DocumentWorkflowService $documentWorkflow)
    {
        $this->createDraftPurchase = $createDraftPurchase;
        $this->documentWorkflow = $documentWorkflow;
    }

    public function execute(PurchaseOrder $order, ?User $actor = null): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $actor) {
            $order = PurchaseOrder::query()
                ->where('tenant_id', TenantContext::currentId())
                ->with('items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->isConverted()) {
                return $order->load('convertedPurchase');
            }

            $requiresApproval = $this->documentWorkflow->requiresApprovalBeforeConversion('purchase_order', $order->company_id, $order->branch_id);

            if (!$order->canConvert($requiresApproval)) {
                throw ValidationException::withMessages([
                    'purchase_order' => $requiresApproval
                        ? 'Purchase order harus berstatus approved sebelum dikonversi ke purchase.'
                        : 'Status purchase order saat ini belum bisa dikonversi ke purchase.',
                ]);
            }

            if ($order->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'purchase_order' => 'Purchase order tidak memiliki item untuk dikonversi.',
                ]);
            }

            $purchase = $this->createDraftPurchase->execute([
                'contact_id' => $order->contact_id,
                'purchase_date' => optional($order->order_date)->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
                'expected_receive_date' => optional($order->expected_receive_date)->format('Y-m-d'),
                'currency_code' => $order->currency_code,
                'tax_rate_id' => data_get($order->meta, 'tax.tax_rate_id'),
                'landed_cost_total' => $order->landed_cost_total,
                'notes' => $order->notes,
                'internal_notes' => $order->internal_notes,
                'items' => $order->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'qty' => $item->qty,
                    'unit_cost' => $item->unit_cost,
                    'discount_total' => $item->discount_total,
                    'tax_total' => $item->tax_total,
                    'notes' => $item->notes,
                ])->all(),
            ], $actor);

            $purchaseMeta = $purchase->meta ?? [];
            $purchaseMeta['purchase_order'] = [
                'purchase_order_id' => $order->id,
                'order_number' => $order->order_number,
            ];
            $purchase->forceFill(['meta' => $purchaseMeta])->save();

            $order->update([
                'status' => PurchaseOrder::STATUS_CONVERTED,
                'converted_at' => now(),
                'converted_purchase_id' => $purchase->id,
                'converted_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            return $order->load('convertedPurchase');
        });
    }
}
