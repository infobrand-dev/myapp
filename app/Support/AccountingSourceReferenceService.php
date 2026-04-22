<?php

namespace App\Support;

use App\Models\AccountingJournal;
use App\Modules\Finance\Models\FinanceTransaction;
use App\Modules\Inventory\Models\StockAdjustment;
use App\Modules\Inventory\Models\StockOpening;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Payments\Models\Payment;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\Models\PurchaseOrder;
use App\Modules\Purchases\Models\PurchaseReceipt;
use App\Modules\Purchases\Models\PurchaseRequest;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleOrder;
use App\Modules\Sales\Models\SaleQuotation;
use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AccountingSourceReferenceService
{
    public function buildForSources($sources): array
    {
        $items = collect($sources)
            ->map(function ($source) {
                if (is_array($source)) {
                    return (object) $source;
                }

                return $source;
            })
            ->filter(function ($source) {
                return is_object($source)
                    && !empty($source->source_type)
                    && !empty($source->source_id);
            })
            ->values();

        if ($items->isEmpty()) {
            return [];
        }

        $loadedSources = $this->loadSources($items);

        return $items->mapWithKeys(function ($source) use ($loadedSources) {
            $sourceType = (string) $source->source_type;
            $sourceId = (int) $source->source_id;
            $sourceKey = $this->sourceKey($sourceType, $sourceId);
            $sourceConfig = $this->supportedSources()[$sourceType] ?? null;
            $sourceModel = $loadedSources[$sourceKey] ?? null;

            return [
                $sourceKey => [
                    'source_url' => $this->sourceUrl($sourceType, $sourceId, $sourceModel, $sourceConfig),
                    'source_label' => $this->sourceLabel($sourceType, $sourceModel, $sourceConfig, $sourceId, 'Journal #' . $sourceId),
                    'source_type_label' => $sourceConfig['label'] ?? 'Source Document',
                    'source_exists' => $sourceModel !== null || $sourceType === AccountingJournal::class,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ],
            ];
        })->all();
    }

    public function buildForJournals($journals): array
    {
        $items = collect($journals)
            ->filter(function ($journal) {
                return is_object($journal) && !empty($journal->id);
            })
            ->values();

        if ($items->isEmpty()) {
            return [];
        }

        $sources = $this->loadSources($items);

        return $items->mapWithKeys(function ($journal) use ($sources) {
            $journalId = (int) $journal->id;
            $sourceType = (string) ($journal->source_type ?? '');
            $sourceId = (int) ($journal->source_id ?? 0);
            $sourceKey = $this->sourceKey($sourceType, $sourceId);
            $sourceConfig = $this->supportedSources()[$sourceType] ?? null;
            $sourceModel = $sources[$sourceKey] ?? null;
            $journalLabel = (string) ($journal->journal_number ?: ('Journal #' . $journalId));

            return [
                $journalId => [
                    'journal_url' => $this->safeRoute('finance.journals.show', $journalId),
                    'journal_label' => $journalLabel,
                    'source_url' => $this->sourceUrl($sourceType, $sourceId, $sourceModel, $sourceConfig),
                    'source_label' => $this->sourceLabel($sourceType, $sourceModel, $sourceConfig, $sourceId, $journalLabel),
                    'source_type_label' => $sourceConfig['label'] ?? 'Source Document',
                    'source_exists' => $sourceModel !== null || $sourceType === AccountingJournal::class,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ],
            ];
        })->all();
    }

    private function loadSources(Collection $journals): array
    {
        $supported = $this->supportedSources();
        $groupedIds = [];

        foreach ($journals as $journal) {
            $sourceType = (string) ($journal->source_type ?? '');
            $sourceId = (int) ($journal->source_id ?? 0);

            if ($sourceId < 1 || !isset($supported[$sourceType]) || $sourceType === AccountingJournal::class) {
                continue;
            }

            if (!isset($groupedIds[$sourceType])) {
                $groupedIds[$sourceType] = [];
            }

            $groupedIds[$sourceType][] = $sourceId;
        }

        $records = [];

        foreach ($groupedIds as $sourceType => $ids) {
            $config = $supported[$sourceType];
            $modelClass = $config['model'];
            $model = new $modelClass();

            $query = $modelClass::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->whereKey(array_values(array_unique($ids)));

            if (BranchContext::currentId() !== null && Schema::hasColumn($model->getTable(), 'branch_id')) {
                $query = BranchContext::applyScope($query);
            }

            foreach ($query->get() as $record) {
                $records[$this->sourceKey($sourceType, (int) $record->getKey())] = $record;
            }
        }

        return $records;
    }

    private function sourceUrl(string $sourceType, int $sourceId, ?Model $sourceModel, ?array $sourceConfig): ?string
    {
        if ($sourceType === AccountingJournal::class) {
            return $sourceId > 0 ? $this->safeRoute('finance.journals.show', $sourceId) : null;
        }

        if (!$sourceConfig || !$sourceModel) {
            return null;
        }

        if (empty($sourceConfig['route'])) {
            return null;
        }

        return $this->safeRoute($sourceConfig['route'], $sourceModel->getKey());
    }

    private function sourceLabel(string $sourceType, ?Model $sourceModel, ?array $sourceConfig, int $sourceId, string $journalLabel): string
    {
        if ($sourceType === AccountingJournal::class) {
            return 'Manual Journal ' . $journalLabel;
        }

        if (!$sourceConfig) {
            return $sourceType !== '' ? class_basename($sourceType) . '#' . $sourceId : 'Source Document';
        }

        if (!$sourceModel) {
            return $sourceConfig['label'] . ' #' . $sourceId;
        }

        $field = $sourceConfig['number_field'];
        $number = trim((string) $sourceModel->{$field});

        if ($number !== '') {
            return $sourceConfig['label'] . ' ' . $number;
        }

        return $sourceConfig['label'] . ' #' . $sourceModel->getKey();
    }

    private function safeRoute(string $name, $parameter): ?string
    {
        try {
            return route($name, $parameter);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function sourceKey(string $sourceType, int $sourceId): string
    {
        return $sourceType . ':' . $sourceId;
    }

    private function supportedSources(): array
    {
        return [
            AccountingJournal::class => [
                'model' => AccountingJournal::class,
                'route' => 'finance.journals.show',
                'number_field' => 'journal_number',
                'label' => 'Manual Journal',
            ],
            FinanceTransaction::class => [
                'model' => FinanceTransaction::class,
                'route' => 'finance.transactions.show',
                'number_field' => 'reference_number',
                'label' => 'Finance Transaction',
            ],
            Payment::class => [
                'model' => Payment::class,
                'route' => 'payments.show',
                'number_field' => 'payment_number',
                'label' => 'Payment',
            ],
            Sale::class => [
                'model' => Sale::class,
                'route' => 'sales.show',
                'number_field' => 'sale_number',
                'label' => 'Sale',
            ],
            SaleQuotation::class => [
                'model' => SaleQuotation::class,
                'route' => 'sales.quotations.show',
                'number_field' => 'quotation_number',
                'label' => 'Quotation',
            ],
            SaleOrder::class => [
                'model' => SaleOrder::class,
                'route' => 'sales.orders.show',
                'number_field' => 'order_number',
                'label' => 'Sales Order',
            ],
            SaleReturn::class => [
                'model' => SaleReturn::class,
                'route' => 'sales.returns.show',
                'number_field' => 'return_number',
                'label' => 'Sale Return',
            ],
            PurchaseRequest::class => [
                'model' => PurchaseRequest::class,
                'route' => 'purchases.requests.show',
                'number_field' => 'request_number',
                'label' => 'Purchase Request',
            ],
            PurchaseOrder::class => [
                'model' => PurchaseOrder::class,
                'route' => 'purchases.orders.show',
                'number_field' => 'order_number',
                'label' => 'Purchase Order',
            ],
            Purchase::class => [
                'model' => Purchase::class,
                'route' => 'purchases.show',
                'number_field' => 'purchase_number',
                'label' => 'Purchase',
            ],
            PurchaseReceipt::class => [
                'model' => PurchaseReceipt::class,
                'route' => null,
                'number_field' => 'receipt_number',
                'label' => 'Purchase Receipt',
            ],
            StockOpening::class => [
                'model' => StockOpening::class,
                'route' => null,
                'number_field' => 'code',
                'label' => 'Opening Stock',
            ],
            StockAdjustment::class => [
                'model' => StockAdjustment::class,
                'route' => 'inventory.adjustments.show',
                'number_field' => 'code',
                'label' => 'Stock Adjustment',
            ],
            StockTransfer::class => [
                'model' => StockTransfer::class,
                'route' => 'inventory.transfers.show',
                'number_field' => 'code',
                'label' => 'Stock Transfer',
            ],
        ];
    }
}
