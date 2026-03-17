<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SaleSnapshotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateDraftSaleAction
{
    private $recalculateTotals;
    private $snapshotService;

    public function __construct(
        RecalculateSaleTotalsAction $recalculateTotals,
        SaleSnapshotService $snapshotService
    ) {
        $this->recalculateTotals = $recalculateTotals;
        $this->snapshotService = $snapshotService;
    }

    public function execute(Sale $sale, array $data, ?User $actor = null): Sale
    {
        if (!$sale->isDraft()) {
            throw ValidationException::withMessages([
                'sale' => 'Hanya draft sale yang boleh diedit.',
            ]);
        }

        return DB::transaction(function () use ($sale, $data, $actor) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            $totals = $this->recalculateTotals->execute($data);
            $contact = !empty($data['contact_id'])
                ? Contact::query()->with('company')->find($data['contact_id'])
                : null;
            $customer = $this->snapshotService->customerSnapshot($contact);

            $sale->update([
                'external_reference' => $data['external_reference'] ?? null,
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
                'currency_code' => $data['currency_code'] ?? $sale->currency_code,
                'notes' => $data['notes'] ?? null,
                'totals_snapshot' => $totals['totals_snapshot'],
                'meta' => array_merge($sale->meta ?? [], [
                    'source_context' => $data['source_context'] ?? ($sale->meta['source_context'] ?? null),
                ]),
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $sale->items()->delete();
            $sale->items()->createMany($totals['items']);

            return $sale->load('items');
        });
    }
}
