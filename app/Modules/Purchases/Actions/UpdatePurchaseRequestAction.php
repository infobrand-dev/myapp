<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Purchases\Models\PurchaseRequest;
use App\Modules\Purchases\Services\PurchaseSnapshotService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePurchaseRequestAction
{
    private $recalculateTotals;
    private $snapshotService;

    public function __construct(
        RecalculatePurchaseTotalsAction $recalculateTotals,
        PurchaseSnapshotService $snapshotService
    ) {
        $this->recalculateTotals = $recalculateTotals;
        $this->snapshotService = $snapshotService;
    }

    public function execute(PurchaseRequest $request, array $data, ?User $actor = null): PurchaseRequest
    {
        if (!$request->isDraft()) {
            throw ValidationException::withMessages([
                'purchase_request' => 'Hanya draft purchase request yang boleh diedit.',
            ]);
        }

        return DB::transaction(function () use ($request, $data, $actor) {
            $request = PurchaseRequest::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(function ($query) {
                    BranchContext::applyScope($query);
                })
                ->lockForUpdate()
                ->findOrFail($request->id);

            $resolvedBranchId = $request->branch_id ?: BranchContext::currentOrDefaultId($actor, CompanyContext::currentId());
            $totals = $this->recalculateTotals->execute($data);
            $supplier = ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($data['contact_id']);
            $snapshot = $this->snapshotService->supplierSnapshot($supplier);
            $meta = $request->meta ?: [];
            $meta['tax'] = data_get($totals, 'tax_context');

            $request->update([
                'contact_id' => $supplier ? $supplier->id : null,
                'supplier_name_snapshot' => $snapshot['name'],
                'supplier_email_snapshot' => $snapshot['email'],
                'supplier_phone_snapshot' => $snapshot['phone'],
                'supplier_address_snapshot' => $snapshot['address'],
                'supplier_snapshot' => $snapshot['payload'],
                'request_date' => $data['purchase_date'],
                'needed_by_date' => array_key_exists('expected_receive_date', $data) ? ($data['expected_receive_date'] ?: null) : $request->needed_by_date,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'landed_cost_total' => $totals['landed_cost_total'],
                'grand_total' => $totals['grand_total'],
                'currency_code' => isset($data['currency_code']) ? $data['currency_code'] : $request->currency_code,
                'notes' => isset($data['notes']) ? $data['notes'] : null,
                'internal_notes' => array_key_exists('internal_notes', $data) ? ($data['internal_notes'] ?: null) : $request->internal_notes,
                'totals_snapshot' => $totals['totals_snapshot'],
                'meta' => $meta,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $request->items()->delete();
            $request->items()->createMany($this->withScope($totals['items'], $resolvedBranchId));

            return $request->load('items');
        });
    }

    private function withScope(array $rows, ?int $branchId): array
    {
        return array_map(function (array $row) use ($branchId): array {
            $row['tenant_id'] = TenantContext::currentId();
            $row['company_id'] = CompanyContext::currentId();
            $row['branch_id'] = $branchId;

            return $row;
        }, $rows);
    }
}
