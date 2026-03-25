<?php

namespace App\Modules\Discounts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Discounts\Actions\UpsertDiscountAction;
use App\Modules\Discounts\Http\Requests\UpsertDiscountRequest;
use App\Modules\Discounts\Models\Discount;
use App\Modules\Discounts\Repositories\DiscountRepository;
use App\Modules\Discounts\Services\DiscountReferenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscountController extends Controller
{
    public function __construct(
        private readonly DiscountRepository $repository,
        private readonly DiscountReferenceService $referenceService,
        private readonly UpsertDiscountAction $upsertAction,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'discount_type', 'status_view']);

        return view('discounts::discounts.index', [
            'discounts' => $this->repository->paginateForIndex($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('discounts::discounts.create', [
            'discount' => new Discount([
                'currency_code' => 'IDR',
                'discount_type' => Discount::TYPE_PERCENTAGE,
                'application_scope' => Discount::SCOPE_ITEM,
                'is_active' => true,
                'stack_mode' => 'stackable',
                'combination_mode' => 'combinable',
                'priority' => 100,
                'sequence' => 100,
                'rule_payload' => ['percentage' => 10],
            ]),
            'references' => $this->referenceService->formOptions(),
        ]);
    }

    public function store(UpsertDiscountRequest $request): RedirectResponse
    {
        $discount = $this->upsertAction->execute($request->validated(), actor: $request->user());

        return redirect()->route('discounts.show', $discount)->with('status', 'Discount berhasil dibuat.');
    }

    public function show(Discount $discount): View
    {
        return view('discounts::discounts.show', [
            'discount' => $this->repository->findForDetail($discount),
        ]);
    }

    public function edit(Discount $discount): View
    {
        return view('discounts::discounts.edit', [
            'discount' => $this->repository->findForEdit($discount),
            'references' => $this->referenceService->formOptions(),
        ]);
    }

    public function update(UpsertDiscountRequest $request, Discount $discount): RedirectResponse
    {
        $discount = $this->upsertAction->execute($request->validated(), $discount, $request->user());

        return redirect()->route('discounts.show', $discount)->with('status', 'Discount berhasil diperbarui.');
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        abort_unless(request()->user()?->can('discounts.delete'), 403);

        if ($discount->usages()->exists()) {
            return back()->with('error', 'Discount tidak bisa dihapus karena sudah pernah digunakan. Gunakan Arsip jika ingin menonaktifkan.');
        }

        $discount->delete();

        return redirect()->route('discounts.index')->with('status', 'Discount dihapus.');
    }

    public function toggleStatus(Discount $discount): RedirectResponse
    {
        abort_unless(request()->user()?->can('discounts.activate'), 403);

        $discount->update(['is_active' => !$discount->is_active]);

        return back()->with('status', 'Status discount diperbarui.');
    }

    public function archive(Discount $discount): RedirectResponse
    {
        abort_unless(request()->user()?->can('discounts.archive'), 403);

        $discount->update([
            'is_archived' => true,
            'is_active' => false,
            'archived_at' => now(),
        ]);

        return redirect()->route('discounts.index')->with('status', 'Discount diarsipkan.');
    }
}
