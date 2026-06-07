<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Search\GlobalSearchService;
use App\Support\Api\ApiResponseFactory;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $search, ApiResponseFactory $responses)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('platform-core.search.max_limit', 25)],
        ]);

        $results = $search->search(
            (string) ($validated['q'] ?? ''),
            (int) ($validated['per_page'] ?? config('platform-core.search.default_limit', 10))
        );

        return $responses->success(
            [
                'items' => $results->items(),
                'query' => (string) ($validated['q'] ?? ''),
            ],
            $responses->paginationMeta($results)
        );
    }
}
