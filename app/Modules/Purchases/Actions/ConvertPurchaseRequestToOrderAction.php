<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Purchases\Models\PurchaseRequest;
use App\Support\BranchContext;
use App\Support\DocumentWorkflowService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertPurchaseRequestToOrderAction
{
    private $createPurchaseOrder;
    private $documentWorkflow;

    public function __construct(CreatePurchaseOrderAction $createPurchaseOrder, DocumentWorkflowService $documentWorkflow)
    {
        $this->createPurchaseOrder = $createPurchaseOrder;
        $this->documentWorkflow = $documentWorkflow;
    }

    public function execute(PurchaseRequest $request, ?User $actor = null): PurchaseRequest
    {
        return DB::transaction(function () use ($request, $actor) {
            $request = PurchaseRequest::query()
                ->where('tenant_id', TenantContext::currentId())
                ->with('items')
                ->tap(function ($query) {
                    BranchContext::applyScope($query);
                })
                ->lockForUpdate()
                ->findOrFail($request->id);

            if ($request->isConverted()) {
                return $request->load('convertedPurchaseOrder');
            }

            $requiresApproval = $this->documentWorkflow->requiresApprovalBeforeConversion('purchase_request', $request->company_id, $request->branch_id);

            if (!$request->canConvert($requiresApproval)) {
                throw ValidationException::withMessages([
                    'purchase_request' => $requiresApproval
                        ? 'Purchase request harus berstatus approved sebelum dikonversi ke purchase order.'
                        : 'Status purchase request saat ini belum bisa dikonversi ke purchase order.',
                ]);
            }

            if ($request->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'purchase_request' => 'Purchase request tidak memiliki item untuk dikonversi.',
                ]);
            }

            $order = $this->createPurchaseOrder->execute([
                'contact_id' => $request->contact_id,
                'order_date' => optional($request->request_date)->format('Y-m-d H:i:s') ?: now()->toDateTimeString(),
                'expected_receive_date' => optional($request->needed_by_date)->format('Y-m-d'),
                'currency_code' => $request->currency_code,
                'tax_rate_id' => data_get($request->meta, 'tax.tax_rate_id'),
                'landed_cost_total' => $request->landed_cost_total,
                'notes' => $request->notes,
                'internal_notes' => $request->internal_notes,
                'items' => $request->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'qty' => $item->qty,
                        'unit_cost' => $item->unit_cost,
                        'discount_total' => $item->discount_total,
                        'tax_total' => $item->tax_total,
                        'notes' => $item->notes,
                    ];
                })->all(),
            ], $actor);

            $orderMeta = $order->meta ?: [];
            $orderMeta['purchase_request'] = [
                'purchase_request_id' => $request->id,
                'request_number' => $request->request_number,
            ];
            $order->forceFill(['meta' => $orderMeta])->save();

            $request->update([
                'status' => PurchaseRequest::STATUS_CONVERTED,
                'converted_at' => now(),
                'converted_purchase_order_id' => $order->id,
                'converted_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            return $request->load('convertedPurchaseOrder');
        });
    }
}
