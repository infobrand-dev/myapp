<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleQuotation;
use App\Support\BranchContext;
use App\Support\DocumentWorkflowService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertSaleQuotationToSaleAction
{
    private $createDraftSale;
    private $documentWorkflow;

    public function __construct(CreateDraftSaleAction $createDraftSale, DocumentWorkflowService $documentWorkflow)
    {
        $this->createDraftSale = $createDraftSale;
        $this->documentWorkflow = $documentWorkflow;
    }

    public function execute(SaleQuotation $quotation, ?User $actor = null): SaleQuotation
    {
        return DB::transaction(function () use ($quotation, $actor) {
            $quotation = SaleQuotation::query()
                ->where('tenant_id', TenantContext::currentId())
                ->with('items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($quotation->id);

            if ($quotation->isConverted()) {
                return $quotation->load('convertedSale');
            }

            $requiresApproval = $this->documentWorkflow->requiresApprovalBeforeConversion('sale_quotation', $quotation->company_id, $quotation->branch_id);

            if (!$quotation->canConvert($requiresApproval)) {
                throw ValidationException::withMessages([
                    'quotation' => $requiresApproval
                        ? 'Quotation harus berstatus approved sebelum dikonversi ke sale.'
                        : 'Status quotation saat ini belum bisa dikonversi ke sale.',
                ]);
            }

            if ($quotation->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'quotation' => 'Quotation tidak memiliki item untuk dikonversi.',
                ]);
            }

            $sale = $this->createDraftSale->execute([
                'contact_id' => $quotation->contact_id,
                'payment_status' => Sale::PAYMENT_UNPAID,
                'source' => Sale::SOURCE_MANUAL,
                'transaction_date' => optional($quotation->quotation_date)->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
                'currency_code' => $quotation->currency_code,
                'header_discount_total' => data_get($quotation->totals_snapshot, 'header_discount_total', 0),
                'header_tax_total' => data_get($quotation->totals_snapshot, 'header_tax_total', 0),
                'tax_rate_id' => data_get($quotation->meta, 'tax.tax_rate_id'),
                'notes' => $quotation->notes,
                'customer_note' => $quotation->customer_note,
                'items' => $quotation->items->map(fn ($item) => [
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
            $saleMeta['quotation'] = [
                'sale_quotation_id' => $quotation->id,
                'quotation_number' => $quotation->quotation_number,
            ];
            $sale->forceFill(['meta' => $saleMeta])->save();

            $quotation->update([
                'status' => SaleQuotation::STATUS_CONVERTED,
                'converted_at' => now(),
                'converted_sale_id' => $sale->id,
                'converted_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            return $quotation->load('convertedSale');
        });
    }
}
