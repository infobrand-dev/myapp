<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SaleIdempotencyService;
use App\Modules\Sales\Services\SaleSnapshotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateDraftSaleAction
{
    private const TENANT_ID = 1;

    private $recalculateTotals;
    private $idempotencyService;
    private $snapshotService;
    private $syncPaymentSummary;

    public function __construct(
        RecalculateSaleTotalsAction $recalculateTotals,
        SaleIdempotencyService $idempotencyService,
        SaleSnapshotService $snapshotService,
        SyncSalePaymentSummaryAction $syncPaymentSummary
    ) {
        $this->recalculateTotals = $recalculateTotals;
        $this->idempotencyService = $idempotencyService;
        $this->snapshotService = $snapshotService;
        $this->syncPaymentSummary = $syncPaymentSummary;
    }

    public function execute(Sale $sale, array $data, ?User $actor = null): Sale
    {
        if (!$sale->isDraft()) {
            throw ValidationException::withMessages([
                'sale' => 'Hanya draft sale yang boleh diedit.',
            ]);
        }

        return DB::transaction(function () use ($sale, $data, $actor) {
            $sale = Sale::query()->where('tenant_id', self::TENANT_ID)->lockForUpdate()->findOrFail($sale->id);
            $totals = $this->recalculateTotals->execute($data);
            $contact = !empty($data['contact_id'])
                ? Contact::query()->with('company')->where('tenant_id', self::TENANT_ID)->find($data['contact_id'])
                : null;
            $customer = $this->snapshotService->customerSnapshot($contact);

            $sale->update([
                'external_reference' => $data['external_reference'] ?? null,
                'idempotency_payload_hash' => $this->idempotencyService->hashFromPayload($data),
                'contact_id' => $contact ? $contact->id : null,
                'customer_name_snapshot' => $customer['name'],
                'customer_email_snapshot' => $customer['email'],
                'customer_phone_snapshot' => $customer['phone'],
                'customer_address_snapshot' => $customer['address'],
                'customer_snapshot' => $customer['payload'],
                'payment_status' => $data['payment_status'],
                'source' => $data['source'],
                'transaction_date' => $data['transaction_date'],
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'balance_due' => $totals['grand_total'],
                'currency_code' => $data['currency_code'] ?? $sale->currency_code,
                'notes' => $data['notes'] ?? null,
                'totals_snapshot' => $totals['totals_snapshot'],
                'meta' => $this->idempotencyService->mergeMeta(array_merge($sale->meta ?? [], [
                    'source_context' => $data['source_context'] ?? ($sale->meta['source_context'] ?? null),
                ]), $data),
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $sale->items()->delete();
            $sale->items()->createMany($this->withTenantId($totals['items']));
            $sale = $this->syncPaymentSummary->execute($sale, $data['payment_status']);

            return $sale->load('items');
        });
    }

    private function withTenantId(array $rows): array
    {
        return array_map(function (array $row): array {
            $row['tenant_id'] = self::TENANT_ID;

            return $row;
        }, $rows);
    }
}
