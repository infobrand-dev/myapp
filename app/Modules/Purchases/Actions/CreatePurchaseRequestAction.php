<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Purchases\Models\PurchaseRequest;
use App\Modules\Purchases\Services\PurchaseRequestNumberService;
use App\Modules\Purchases\Services\PurchaseSnapshotService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreatePurchaseRequestAction
{
    private $recalculateTotals;
    private $numberService;
    private $snapshotService;

    public function __construct(
        RecalculatePurchaseTotalsAction $recalculateTotals,
        PurchaseRequestNumberService $numberService,
        PurchaseSnapshotService $snapshotService
    ) {
        $this->recalculateTotals = $recalculateTotals;
        $this->numberService = $numberService;
        $this->snapshotService = $snapshotService;
    }

    public function execute(array $data, ?User $actor = null): PurchaseRequest
    {
        return DB::transaction(function () use ($data, $actor) {
            $resolvedBranchId = BranchContext::currentOrDefaultId($actor, CompanyContext::currentId());
            $totals = $this->recalculateTotals->execute($data);
            $supplier = ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($data['contact_id']);
            $snapshot = $this->snapshotService->supplierSnapshot($supplier);

            $request = PurchaseRequest::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $resolvedBranchId,
                'request_number' => $this->numberService->generate(
                    !empty($data['purchase_date']) ? new \DateTimeImmutable((string) $data['purchase_date']) : null
                ),
                'contact_id' => $supplier ? $supplier->id : null,
                'supplier_name_snapshot' => $snapshot['name'],
                'supplier_email_snapshot' => $snapshot['email'],
                'supplier_phone_snapshot' => $snapshot['phone'],
                'supplier_address_snapshot' => $snapshot['address'],
                'supplier_snapshot' => $snapshot['payload'],
                'status' => PurchaseRequest::STATUS_DRAFT,
                'request_date' => $data['purchase_date'],
                'needed_by_date' => isset($data['expected_receive_date']) ? $data['expected_receive_date'] : null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'landed_cost_total' => $totals['landed_cost_total'],
                'grand_total' => $totals['grand_total'],
                'currency_code' => isset($data['currency_code']) ? $data['currency_code'] : 'IDR',
                'notes' => isset($data['notes']) ? $data['notes'] : null,
                'internal_notes' => isset($data['internal_notes']) ? $data['internal_notes'] : null,
                'totals_snapshot' => $totals['totals_snapshot'],
                'meta' => array_filter([
                    'tax' => data_get($totals, 'tax_context'),
                ], function ($value) {
                    return $value !== null;
                }),
                'created_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
            ]);

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
