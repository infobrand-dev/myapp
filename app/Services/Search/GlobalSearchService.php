<?php

namespace App\Services\Search;

use App\Models\SearchDocument;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GlobalSearchService
{
    public function search(string $query, int $perPage = 10): LengthAwarePaginator
    {
        $query = trim($query);
        $tenantId = TenantContext::currentId();
        $companyId = CompanyContext::currentId();
        $branchId = BranchContext::currentId();

        $builder = SearchDocument::query()
            ->where('tenant_id', $tenantId)
            ->when($companyId, function (Builder $builder) use ($companyId) {
                $builder->where(function (Builder $nested) use ($companyId) {
                    $nested->whereNull('company_id')
                        ->orWhere('company_id', $companyId);
                });
            })
            ->when($branchId !== null, function (Builder $builder) use ($branchId) {
                $builder->where(function (Builder $nested) use ($branchId) {
                    $nested->whereNull('branch_id')
                        ->orWhere('branch_id', $branchId);
                });
            });

        if ($query !== '') {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'pgsql') {
                $builder->where(function (Builder $nested) use ($query) {
                    $nested->whereRaw(
                        "to_tsvector('simple', coalesce(search_vector, '')) @@ websearch_to_tsquery('simple', ?)",
                        [$query]
                    )->orWhereRaw('similarity(title, ?) > 0.2', [$query]);
                });
            } else {
                $builder->where(function (Builder $nested) use ($query) {
                    $nested->where('title', 'like', '%' . $query . '%')
                        ->orWhere('subtitle', 'like', '%' . $query . '%')
                        ->orWhere('snippet', 'like', '%' . $query . '%');
                });
            }
        }

        return $builder
            ->orderByDesc('indexed_at')
            ->orderBy('title')
            ->paginate(
                min(max($perPage, 1), (int) config('platform-core.search.max_limit', 25))
            );
    }
}
