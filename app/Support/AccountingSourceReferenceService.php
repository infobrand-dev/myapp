<?php

namespace App\Support;

use App\Models\AccountingJournal;
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
        return array_merge([
            AccountingJournal::class => [
                'model' => AccountingJournal::class,
                'route' => 'finance.journals.show',
                'number_field' => 'journal_number',
                'label' => 'Manual Journal',
            ],
        ], config('platform-core.accounting.source_references', []));
    }
}
