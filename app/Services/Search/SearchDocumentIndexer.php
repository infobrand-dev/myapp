<?php

namespace App\Services\Search;

use App\Models\SearchDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SearchDocumentIndexer
{
    public function __construct(
        private readonly SearchDocumentRegistry $registry
    ) {
    }

    public function upsert(Model $model, string $type): void
    {
        $definition = $this->registry->definitionForType($type);
        if (!$definition) {
            return;
        }

        $title = (string) $definition['title']($model);
        $subtitle = $definition['subtitle']($model);
        $snippet = $definition['snippet']($model);
        $url = $definition['url']($model);
        $searchVector = $this->makeSearchVector($title, $subtitle, $snippet);

        SearchDocument::query()->updateOrCreate(
            [
                'document_type' => $type,
                'document_id' => (string) $model->getKey(),
            ],
            [
                'tenant_id' => (int) ($model->tenant_id ?? 1),
                'company_id' => $model->company_id ?? null,
                'branch_id' => $model->branch_id ?? null,
                'title' => $title,
                'subtitle' => $subtitle,
                'snippet' => $snippet,
                'url' => $url,
                'search_vector' => $searchVector,
                'meta' => ['model' => get_class($model)],
                'indexed_at' => now(),
            ]
        );
    }

    public function rebuild(): void
    {
        foreach ($this->registry->definitions() as $type => $definition) {
            /** @var class-string<Model> $modelClass */
            $modelClass = $definition['model'];
            $seenDocumentIds = [];

            $modelClass::query()->orderBy('id')->chunk(200, function ($models) use ($type) {
                foreach ($models as $model) {
                    $this->upsert($model, $type);
                }
            });

            $modelClass::query()->orderBy('id')->chunk(200, function ($models) use (&$seenDocumentIds) {
                foreach ($models as $model) {
                    $seenDocumentIds[] = (string) $model->getKey();
                }
            });

            SearchDocument::query()
                ->where('document_type', $type)
                ->when($seenDocumentIds !== [], function ($query) use ($seenDocumentIds) {
                    $query->whereNotIn('document_id', $seenDocumentIds);
                }, function ($query) {
                    $query->whereRaw('1 = 1');
                })
                ->delete();
        }
    }

    private function makeSearchVector(?string ...$parts): string
    {
        $parts = array_values(array_filter(array_map(
            fn (?string $part) => trim((string) $part),
            $parts
        )));

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return implode(' ', $parts);
        }

        return implode(' ', $parts);
    }
}
