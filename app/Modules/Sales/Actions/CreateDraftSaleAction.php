<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SaleIdempotencyService;
use App\Modules\Sales\Services\SaleNumberService;
use App\Modules\Sales\Services\SaleSnapshotService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
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
            $resolvedBranchId = isset($data['branch_id']) && $data['branch_id'] !== null && $data['branch_id'] !== ''
                ? (int) $data['branch_id']
                : BranchContext::currentOrDefaultId($actor, CompanyContext::currentId());

            if (!empty($data['external_reference'])) {
                $existingSale = Sale::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())
                    ->where('source', $data['source'])
                    ->where('external_reference', $data['external_reference'])
                    ->first();

                if ($existingSale) {
                    $this->idempotencyService->assertMatches($existingSale, $data);

                    return $existingSale->load('items');
                }
            }

            $totals = $this->recalculateTotals->execute($data);
            $attachmentPath = $this->storeAttachment($data['attachment'] ?? null);
            $contact = !empty($data['contact_id'])
                ? ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($data['contact_id'])
                : null;
            $customer = $this->snapshotService->customerSnapshot($contact);

            try {
                $sale = Sale::query()->create([
                    'tenant_id' => TenantContext::currentId(),
                    'company_id' => CompanyContext::currentId(),
                    'sale_number' => $this->saleNumberService->generate(
                        !empty($data['transaction_date']) ? new \DateTimeImmutable((string) $data['transaction_date']) : null,
                        CompanyContext::currentId(),
                        $resolvedBranchId
                    ),
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
                    'branch_id' => $resolvedBranchId,
                    'transaction_date' => $data['transaction_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'subtotal' => $totals['subtotal'],
                    'discount_total' => $totals['discount_total'],
                    'tax_total' => $totals['tax_total'],
                    'grand_total' => $totals['grand_total'],
                    'paid_total' => 0,
                    'balance_due' => $totals['grand_total'],
                    'currency_code' => $data['currency_code'] ?? 'IDR',
                    'notes' => $data['notes'] ?? null,
                    'customer_note' => $data['customer_note'] ?? null,
                    'attachment_path' => $attachmentPath,
                    'totals_snapshot' => $totals['totals_snapshot'],
                    'meta' => $this->idempotencyService->mergeMeta([
                        'source_context' => $this->sourceContext($data),
                        'tax' => $this->taxContext($totals),
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
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())
                    ->where('source', $data['source'])
                    ->where('external_reference', $data['external_reference'])
                    ->first();

                if (!$sale) {
                    throw $exception;
                }

                $this->idempotencyService->assertMatches($sale, $data);

                return $sale->load('items');
            }

            $sale->items()->createMany($this->withTenantId($totals['items']));
            $sale = $this->syncPaymentSummary->execute($sale, $data['payment_status']);
            $sale->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
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

    private function withTenantId(array $rows): array
    {
        return array_map(function (array $row): array {
            $row['tenant_id'] = TenantContext::currentId();
            $row['company_id'] = CompanyContext::currentId();

            return $row;
        }, $rows);
    }

    private function storeAttachment(mixed $attachment): ?string
    {
        if (!$attachment instanceof UploadedFile) {
            return null;
        }

        return $attachment->store('sales/attachments', 'public');
    }

    private function sourceContext(array $data): ?array
    {
        $context = is_array($data['source_context'] ?? null)
            ? $data['source_context']
            : [];

        $inventoryLocationId = isset($data['inventory_location_id']) && $data['inventory_location_id'] !== null && $data['inventory_location_id'] !== ''
            ? (int) $data['inventory_location_id']
            : (isset($context['inventory_location_id']) ? (int) $context['inventory_location_id'] : null);

        if ($inventoryLocationId) {
            $context['inventory_location_id'] = $inventoryLocationId;
        } else {
            unset($context['inventory_location_id']);
        }

        return $context !== [] ? $context : null;
    }

    private function taxContext(array $totals): ?array
    {
        $context = data_get($totals, 'tax_context');

        if (!is_array($context)) {
            return null;
        }

        if (empty($context['tax_rate_id']) && empty($context['tax_snapshot'])) {
            return null;
        }

        return $context;
    }
}
