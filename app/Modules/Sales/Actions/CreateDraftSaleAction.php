<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SaleIdempotencyService;
use App\Modules\Sales\Services\SaleNumberService;
use App\Modules\Sales\Services\SaleSnapshotService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CreateDraftSaleAction
{
    private $recalculateTotals;
    private $saleNumberService;
    private $idempotencyService;
    private $snapshotService;
    private $syncPaymentSummary;

    public function __construct(
        RecalculateSaleTotalsAction $recalculateTotals,
        SaleNumberService $saleNumberService,
        SaleIdempotencyService $idempotencyService,
        SaleSnapshotService $snapshotService,
        SyncSalePaymentSummaryAction $syncPaymentSummary
    ) {
        $this->recalculateTotals = $recalculateTotals;
        $this->saleNumberService = $saleNumberService;
        $this->idempotencyService = $idempotencyService;
        $this->snapshotService = $snapshotService;
        $this->syncPaymentSummary = $syncPaymentSummary;
    }

    public function execute(array $data, ?User $actor = null): Sale
    {
        return DB::transaction(function () use ($data, $actor) {
            if (!empty($data['external_reference'])) {
                $existingSale = Sale::query()
                    ->where('source', $data['source'])
                    ->where('external_reference', $data['external_reference'])
                    ->first();

                if ($existingSale) {
                    $this->idempotencyService->assertMatches($existingSale, $data);

                    return $existingSale->load('items');
                }
            }

            $totals = $this->recalculateTotals->execute($data);
            $contact = !empty($data['contact_id'])
                ? Contact::query()->with('company')->find($data['contact_id'])
                : null;
            $customer = $this->snapshotService->customerSnapshot($contact);

            try {
                $sale = Sale::query()->create([
                    'sale_number' => $this->saleNumberService->generate(),
                    'external_reference' => $data['external_reference'] ?? null,
                    'idempotency_payload_hash' => $this->idempotencyService->hashFromPayload($data),
                    'contact_id' => $contact ? $contact->id : null,
                    'customer_name_snapshot' => $customer['name'],
                    'customer_email_snapshot' => $customer['email'],
                    'customer_phone_snapshot' => $customer['phone'],
                    'customer_address_snapshot' => $customer['address'],
                    'customer_snapshot' => $customer['payload'],
                    'status' => Sale::STATUS_DRAFT,
                    'payment_status' => $data['payment_status'],
                    'source' => $data['source'],
                    'transaction_date' => $data['transaction_date'],
                    'subtotal' => $totals['subtotal'],
                    'discount_total' => $totals['discount_total'],
                    'tax_total' => $totals['tax_total'],
                    'grand_total' => $totals['grand_total'],
                    'paid_total' => 0,
                    'balance_due' => $totals['grand_total'],
                    'currency_code' => $data['currency_code'] ?? 'IDR',
                    'notes' => $data['notes'] ?? null,
                    'totals_snapshot' => $totals['totals_snapshot'],
                    'meta' => $this->idempotencyService->mergeMeta([
                        'source_context' => $data['source_context'] ?? null,
                        'draft_created_from' => $data['source'],
                    ], $data),
                    'created_by' => $actor ? $actor->id : null,
                    'updated_by' => $actor ? $actor->id : null,
                ]);
            } catch (QueryException $exception) {
                if (empty($data['external_reference'])) {
                    throw $exception;
                }

                $sale = Sale::query()
                    ->where('source', $data['source'])
                    ->where('external_reference', $data['external_reference'])
                    ->first();

                if (!$sale) {
                    throw $exception;
                }

                $this->idempotencyService->assertMatches($sale, $data);

                return $sale->load('items');
            }

            $sale->items()->createMany($totals['items']);
            $sale = $this->syncPaymentSummary->execute($sale, $data['payment_status']);
            $sale->statusHistories()->create([
                'from_status' => null,
                'to_status' => Sale::STATUS_DRAFT,
                'event' => 'created',
                'actor_id' => $actor ? $actor->id : null,
                'meta' => [
                    'sale_number' => $sale->sale_number,
                ],
            ]);

            return $sale->load('items');
        });
    }
}
