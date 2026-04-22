<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Sales\Models\SaleQuotation;
use App\Modules\Sales\Services\SaleSnapshotService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSaleQuotationAction
{
    public function __construct(
        private readonly RecalculateSaleTotalsAction $recalculateTotals,
        private readonly SaleSnapshotService $snapshotService,
    ) {
    }

    public function execute(SaleQuotation $quotation, array $data, ?User $actor = null): SaleQuotation
    {
        if (!$quotation->isDraft()) {
            throw ValidationException::withMessages([
                'quotation' => 'Hanya draft quotation yang boleh diedit.',
            ]);
        }

        return DB::transaction(function () use ($quotation, $data, $actor) {
            $quotation = SaleQuotation::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($quotation->id);

            $resolvedBranchId = $quotation->branch_id ?: BranchContext::currentOrDefaultId($actor, CompanyContext::currentId());
            $totals = $this->recalculateTotals->execute($data);
            $contact = !empty($data['contact_id'])
                ? ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($data['contact_id'])
                : null;
            $customer = $this->snapshotService->customerSnapshot($contact);
            $meta = $quotation->meta ?? [];
            $meta['tax'] = data_get($totals, 'tax_context');

            $quotation->update([
                'contact_id' => $contact?->id,
                'customer_name_snapshot' => $customer['name'],
                'customer_email_snapshot' => $customer['email'],
                'customer_phone_snapshot' => $customer['phone'],
                'customer_address_snapshot' => $customer['address'],
                'customer_snapshot' => $customer['payload'],
                'quotation_date' => $data['quotation_date'],
                'valid_until_date' => array_key_exists('valid_until_date', $data) ? ($data['valid_until_date'] ?? null) : $quotation->valid_until_date,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'currency_code' => $data['currency_code'] ?? $quotation->currency_code,
                'notes' => $data['notes'] ?? null,
                'customer_note' => $data['customer_note'] ?? null,
                'totals_snapshot' => $totals['totals_snapshot'],
                'meta' => $meta,
                'updated_by' => $actor?->id,
            ]);

            $quotation->items()->delete();
            $quotation->items()->createMany($this->withTenantId($totals['items'], $resolvedBranchId));

            return $quotation->load('items');
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
}
