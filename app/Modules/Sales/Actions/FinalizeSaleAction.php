<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Sales\Events\SaleFinalized;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SaleSnapshotService;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeSaleAction
{
    private $recalculateTotals;
    private $snapshotService;
    private $recordSalePayment;
    private $syncPaymentSummary;
    private $journalService;
    private $periodLockService;

    public function __construct(
        RecalculateSaleTotalsAction $recalculateTotals,
        SaleSnapshotService $snapshotService,
        RecordSalePaymentAction $recordSalePayment,
        SyncSalePaymentSummaryAction $syncPaymentSummary,
        AccountingJournalService $journalService,
        AccountingPeriodLockService $periodLockService
    ) {
        $this->recalculateTotals = $recalculateTotals;
        $this->snapshotService = $snapshotService;
        $this->recordSalePayment = $recordSalePayment;
        $this->syncPaymentSummary = $syncPaymentSummary;
        $this->journalService = $journalService;
        $this->periodLockService = $periodLockService;
    }

    public function execute(Sale $sale, array $data, ?User $actor = null): Sale
    {
        $sale = DB::transaction(function () use ($sale, $data, $actor) {
            $sale = Sale::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if (!$sale->isDraft()) {
                throw ValidationException::withMessages([
                    'sale' => 'Hanya draft sale yang dapat di-finalize.',
                ]);
            }

            $this->periodLockService->ensureDateOpen($sale->transaction_date ?: now(), $sale->branch_id, 'finalize sale');

            $payload = [
                'header_discount_total' => data_get($sale->totals_snapshot, 'header_discount_total', 0),
                'header_tax_total' => data_get($sale->totals_snapshot, 'header_tax_total', 0),
                'items' => $sale->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'discount_total' => $item->discount_total,
                    'tax_total' => $item->tax_total,
                    'notes' => $item->notes,
                ])->all(),
            ];
            $totals = $this->recalculateTotals->execute($payload);
            $contact = $sale->contact_id
                ? ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($sale->contact_id)
                : null;
            $customer = $this->snapshotService->customerSnapshot($contact);

            $sale->items()->delete();
            $sale->items()->createMany($this->withTenantId($totals['items']));

            $fromStatus = $sale->status;
            $sale->update([
                'customer_name_snapshot' => $customer['name'],
                'customer_email_snapshot' => $customer['email'],
                'customer_phone_snapshot' => $customer['phone'],
                'customer_address_snapshot' => $customer['address'],
                'customer_snapshot' => $customer['payload'],
                'status' => Sale::STATUS_FINALIZED,
                'payment_status' => $data['payment_status'] ?? $sale->payment_status,
                'transaction_date' => $sale->transaction_date ?: now(),
                'due_date' => array_key_exists('due_date', $data) ? ($data['due_date'] ?? null) : $sale->due_date,
                'finalized_at' => now(),
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'balance_due' => $totals['grand_total'],
                'totals_snapshot' => array_merge($totals['totals_snapshot'], [
                    'finalized_at' => now()->toDateTimeString(),
                ]),
                'updated_by' => $actor ? $actor->id : null,
                'finalized_by' => $actor ? $actor->id : null,
            ]);

            $sale->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'from_status' => $fromStatus,
                'to_status' => Sale::STATUS_FINALIZED,
                'event' => 'finalized',
                'reason' => $data['reason'] ?? null,
                'actor_id' => $actor ? $actor->id : null,
                'meta' => [
                    'payment_status' => $sale->payment_status,
                    'source' => $sale->source,
                    'subtotal' => (float) $sale->subtotal,
                    'discount_total' => (float) $sale->discount_total,
                    'tax_total' => (float) $sale->tax_total,
                    'grand_total' => (float) $sale->grand_total,
                ],
            ]);

            if (!empty($data['payments']) && is_array($data['payments'])) {
                foreach ($data['payments'] as $paymentRow) {
                    $this->recordSalePayment->execute($sale, $paymentRow, $actor);
                }

                $sale = Sale::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())
                    ->tap(fn ($query) => BranchContext::applyScope($query))
                    ->findOrFail($sale->id);
            } else {
                $sale = $this->syncPaymentSummary->execute($sale, $data['payment_status'] ?? $sale->payment_status);
            }

            $this->journalService->sync(
                $sale,
                'sale_finalized',
                $sale->transaction_date ?: now(),
                $this->journalLines($sale),
                [
                    'payment_status' => $sale->payment_status,
                    'grand_total' => (float) $sale->grand_total,
                ],
                'Auto journal sale ' . $sale->sale_number
            );

            return $sale->load('items', 'paymentAllocations.payment.method', 'statusHistories');
        });

        event(new SaleFinalized($sale));

        return $sale;
    }

    private function withTenantId(array $rows): array
    {
        return array_map(function (array $row): array {
            $row['tenant_id'] = TenantContext::currentId();
            $row['company_id'] = CompanyContext::currentId();

            return $row;
        }, $rows);
    }

    private function journalLines(Sale $sale): array
    {
        $lines = [
            [
                'account_code' => 'AR',
                'account_name' => 'Accounts Receivable',
                'debit' => (float) $sale->grand_total,
                'credit' => 0,
            ],
            [
                'account_code' => 'SALES',
                'account_name' => 'Sales Revenue',
                'debit' => 0,
                'credit' => (float) $sale->subtotal,
            ],
        ];

        if ((float) $sale->discount_total > 0) {
            $lines[] = [
                'account_code' => 'SALES_DISC',
                'account_name' => 'Sales Discount',
                'debit' => (float) $sale->discount_total,
                'credit' => 0,
            ];
        }

        if ((float) $sale->tax_total > 0) {
            $lines[] = [
                'account_code' => 'SALES_TAX',
                'account_name' => 'Sales Tax Payable',
                'debit' => 0,
                'credit' => (float) $sale->tax_total,
            ];
        }

        return $lines;
    }
}
