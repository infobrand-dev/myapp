<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Http\Requests\StoreInventoryLocationRequest;
use App\Modules\Inventory\Http\Requests\UpdateInventoryLocationRequest;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryLocationController extends Controller
{
    public function index(): View
    {
        $locations = InventoryLocation::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->with(['parent', 'branch'])
            ->withCount(['stocks', 'children'])
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(15);

        return view('inventory::locations.index', [
            'locations' => $locations,
        ]);
    }

    public function create(): View
    {
        return view('inventory::locations.create', [
            'location' => new InventoryLocation([
                'type' => 'warehouse',
                'is_active' => true,
                'is_default' => false,
            ]),
            'parentOptions' => $this->parentOptions(),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function store(StoreInventoryLocationRequest $request): RedirectResponse
    {
        $location = DB::transaction(function () use ($request) {
            $data = $request->validated();

            if (!empty($data['is_default'])) {
                $this->clearExistingDefault();
            }

            return InventoryLocation::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => BranchContext::currentId(),
                'parent_id' => $data['parent_id'] ?? null,
                'code' => strtoupper((string) $data['code']),
                'name' => $data['name'],
                'type' => $data['type'],
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'meta' => ['managed_from_ui' => true],
            ]);
        });

        return redirect()->route('inventory.locations.index')->with('status', "Location {$location->name} dibuat.");
    }

    public function edit(InventoryLocation $location): View
    {
        return view('inventory::locations.edit', [
            'location' => $location,
            'parentOptions' => $this->parentOptions($location->id),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function update(UpdateInventoryLocationRequest $request, InventoryLocation $location): RedirectResponse
    {
        DB::transaction(function () use ($request, $location) {
            $data = $request->validated();

            if (!empty($data['is_default'])) {
                $this->clearExistingDefault($location->id);
            }

            $location->update([
                'parent_id' => $data['parent_id'] ?? null,
                'code' => strtoupper((string) $data['code']),
                'name' => $data['name'],
                'type' => $data['type'],
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? false),
                'meta' => array_merge(is_array($location->meta) ? $location->meta : [], [
                    'managed_from_ui' => true,
                ]),
            ]);
        });

        return redirect()->route('inventory.locations.index')->with('status', "Location {$location->name} diperbarui.");
    }

    private function parentOptions(?int $exceptId = null)
    {
        return InventoryLocation::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function clearExistingDefault(?int $exceptId = null): void
    {
        InventoryLocation::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->update(['is_default' => false]);
    }

    private function typeOptions(): array
    {
        return [
            'warehouse' => 'Warehouse',
            'storefront' => 'Storefront',
            'staging' => 'Staging',
            'returns' => 'Returns',
        ];
    }
}
