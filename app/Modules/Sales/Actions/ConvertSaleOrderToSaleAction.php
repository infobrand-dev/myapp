<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleOrder;
use App\Support\BranchContext;
use App\Support\DocumentWorkflowService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertSaleOrderToSaleAction
{
    private $createDraftSale;
    private $documentWorkflow;

    public function __construct(CreateDraftSaleAction $createDraftSale, DocumentWorkflowService $documentWorkflow)
    {
        $this->createDraftSale = $createDraftSale;
        $this->documentWorkflow = $documentWorkflow;
    }

    public function execute(SaleOrder $order, ?User $actor = null): SaleOrder
    {
        return DB::transaction(function () use ($order, $actor) {
            $order = SaleOrder::query()
                ->where('tenant_id', TenantContext::currentId())
                ->with('items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->isConverted()) {
                return $order->load('convertedSale');
            }

            $requiresApproval = $this->documentWorkflow->requiresApprovalBeforeConversion('sale_order', $order->company_id, $order->branch_id);

            if (!$order->canConvert($requiresApproval)) {
                throw ValidationException::withMessages([
                    'sale_order' => $requiresApproval
                        ? 'Sales order harus berstatus approved sebelum dikonversi ke sale.'
                        : 'Status sales order saat ini belum bisa dikonversi ke sale.',
                ]);
            }

            if ($order->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'sale_order' => 'Sales order tidak memiliki item untuk dikonversi.',
                ]);
            }

            $sale = $this->createDraftSale->execute([
                'contact_id' => $order->contact_id,
                'payment_status' => Sale::PAYMENT_UNPAID,
                'source' => Sale::SOURCE_MANUAL,
                'transaction_date' => optional($order->order_date)->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
                'currency_code' => $order->currency_code,
                'header_discount_total' => data_get($order->totals_snapshot, 'header_discount_total', 0),
                'header_tax_total' => data_get($order->totals_snapshot, 'header_tax_total', 0),
                'tax_rate_id' => data_get($order->meta, 'tax.tax_rate_id'),
                'notes' => $order->notes,
                'customer_note' => $order->customer_note,
                'items' => $order->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'discount_total' => $item->discount_total,
                    'tax_total' => $item->tax_total,
                    'notes' => $item->notes,
                ])->all(),
            ], $actor);

            $saleMeta = $sale->meta ?? [];
            $saleMeta['sales_order'] = [
                'sale_order_id' => $order->id,
                'order_number' => $order->order_number,
            ];
            $sale->forceFill(['meta' => $saleMeta])->save();

            $order->update([
                'status' => SaleOrder::STATUS_CONVERTED,
                'converted_at' => now(),
                'converted_sale_id' => $sale->id,
                'converted_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            return $order->load('convertedSale');
        });
    }
}
