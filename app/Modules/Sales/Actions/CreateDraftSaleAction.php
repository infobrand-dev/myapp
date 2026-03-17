<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SaleNumberService;
use App\Modules\Sales\Services\SaleSnapshotService;
use Illuminate\Support\Facades\DB;

class CreateDraftSaleAction
{
    private $recalculateTotals;
    private $saleNumberService;
    private $snapshotService;

    public function __construct(
        RecalculateSaleTotalsAction $recalculateTotals,
        SaleNumberService $saleNumberService,
        SaleSnapshotService $snapshotService
    ) {
        $this->recalculateTotals = $recalculateTotals;
        $this->saleNumberService = $saleNumberService;
        $this->snapshotService = $snapshotService;
    }

    public function execute(array $data, ?User $actor = null): Sale
    {
        return DB::transaction(function () use ($data, $actor) {
            $totals = $this->recalculateTotals->execute($data);
            $contact = !empty($data['contact_id'])
                ? Contact::query()->with('company')->find($data['contact_id'])
                : null;
            $customer = $this->snapshotService->customerSnapshot($contact);

            $sale = Sale::query()->create([
                'sale_number' => $this->saleNumberService->generate(),
                'external_reference' => $data['external_reference'] ?? null,
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
                'currency_code' => $data['currency_code'] ?? 'IDR',
                'notes' => $data['notes'] ?? null,
                'totals_snapshot' => $totals['totals_snapshot'],
                'meta' => [
                    'source_context' => $data['source_context'] ?? null,
                    'draft_created_from' => $data['source'],
                ],
                'created_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $sale->items()->createMany($totals['items']);
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
