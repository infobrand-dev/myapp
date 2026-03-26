<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Requests\StoreFinanceCategoryRequest;
use App\Modules\Finance\Http\Requests\UpdateFinanceCategoryRequest;
use App\Modules\Finance\Models\FinanceCategory;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FinanceCategoryController extends Controller
{
    public function index(): View
    {
        return view('finance::categories.index', [
            'categories' => FinanceCategory::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->withCount('transactions')
                ->orderBy('transaction_type')
                ->orderBy('name')
                ->get(),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function store(StoreFinanceCategoryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            FinanceCategory::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'name' => $request->input('name'),
                'slug' => Str::slug($request->input('name')) . '-' . Str::lower(Str::random(4)),
                'transaction_type' => $request->input('transaction_type'),
                'is_active' => $request->boolean('is_active', true),
                'notes' => $request->input('notes'),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('finance.categories.index')->with('status', 'Kategori ditambahkan.');
    }

    public function edit(FinanceCategory $category): View
    {
        return view('finance::categories.edit', [
            'category' => $category,
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function update(FinanceCategory $category, UpdateFinanceCategoryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($category, $request) {
            $category->update([
                'name' => $request->input('name'),
                'transaction_type' => $request->input('transaction_type'),
                'is_active' => $request->boolean('is_active'),
                'notes' => $request->input('notes'),
                'updated_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('finance.categories.index')->with('status', 'Kategori diperbarui.');
    }

    public function destroy(FinanceCategory $category): RedirectResponse
    {
        if ($category->transactions()->count() > 0) {
            return redirect()->route('finance.categories.index')->with('error', 'Tidak bisa dihapus — kategori masih digunakan.');
        }

        $category->delete();

        return redirect()->route('finance.categories.index')->with('success', 'Kategori dihapus.');
    }

    private function typeOptions(): array
    {
        return [
            FinanceCategory::TYPE_CASH_IN => 'Cash In',
            FinanceCategory::TYPE_CASH_OUT => 'Cash Out',
            FinanceCategory::TYPE_EXPENSE => 'Expense',
        ];
    }
}
