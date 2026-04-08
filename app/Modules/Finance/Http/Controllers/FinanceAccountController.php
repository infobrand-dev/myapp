<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Requests\StoreFinanceAccountRequest;
use App\Modules\Finance\Http\Requests\UpdateFinanceAccountRequest;
use App\Modules\Finance\Models\FinanceAccount;
use App\Modules\Finance\Services\FinanceAccountProvisioner;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceAccountController extends Controller
{
    public function index(FinanceAccountProvisioner $provisioner): View
    {
        $companyId = $this->requireCurrentCompanyId();
        $provisioner->ensureDefaults(TenantContext::currentId(), $companyId, auth()->id());

        return view('finance::accounts.index', [
            'accounts' => FinanceAccount::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->withCount('transactions')
                ->orderByDesc('is_default')
                ->orderBy('account_type')
                ->orderBy('name')
                ->get(),
            'typeOptions' => FinanceAccount::typeOptions(),
        ]);
    }

    public function edit(FinanceAccount $account): View
    {
        return view('finance::accounts.edit', [
            'account' => $account,
            'typeOptions' => FinanceAccount::typeOptions(),
        ]);
    }

    public function store(StoreFinanceAccountRequest $request): RedirectResponse
    {
        $companyId = $this->requireCurrentCompanyId();

        DB::transaction(function () use ($request, $companyId): void {
            $isDefault = $request->boolean('is_default');

            if ($isDefault) {
                $this->clearDefaultAccount();
            }

            FinanceAccount::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => $companyId,
                'name' => $request->input('name'),
                'slug' => $request->filled('slug')
                    ? Str::slug((string) $request->input('slug'))
                    : Str::slug((string) $request->input('name')) . '-' . Str::lower(Str::random(4)),
                'account_type' => $request->input('account_type'),
                'account_number' => $request->input('account_number'),
                'is_active' => $request->boolean('is_active', true),
                'is_default' => $isDefault,
                'notes' => $request->input('notes'),
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);
        });

        return redirect()->route('finance.accounts.index')->with('status', 'Finance account ditambahkan.');
    }

    public function update(FinanceAccount $account, UpdateFinanceAccountRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($account, $request): void {
            $isDefault = $request->boolean('is_default');

            if ($isDefault) {
                $this->clearDefaultAccount($account->id);
            }

            $account->update([
                'name' => $request->input('name'),
                'slug' => $request->filled('slug') ? Str::slug((string) $request->input('slug')) : $account->slug,
                'account_type' => $request->input('account_type'),
                'account_number' => $request->input('account_number'),
                'is_active' => $request->boolean('is_active'),
                'is_default' => $isDefault,
                'notes' => $request->input('notes'),
                'updated_by' => $request->user()?->id,
            ]);
        });

        return redirect()->route('finance.accounts.index')->with('status', 'Finance account diperbarui.');
    }

    public function destroy(FinanceAccount $account): RedirectResponse
    {
        if ($account->transactions()->exists()) {
            return redirect()->route('finance.accounts.index')->with('error', 'Tidak bisa dihapus karena account sudah dipakai transaksi.');
        }

        $account->delete();

        return redirect()->route('finance.accounts.index')->with('status', 'Finance account dihapus.');
    }

    private function clearDefaultAccount(?int $exceptId = null): void
    {
        FinanceAccount::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', $this->requireCurrentCompanyId())
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->update(['is_default' => false]);
    }

    private function requireCurrentCompanyId(): int
    {
        $companyId = CompanyContext::currentId();

        if ($companyId) {
            return (int) $companyId;
        }

        throw ValidationException::withMessages([
            'company' => 'Pilih company aktif terlebih dahulu sebelum mengelola finance account.',
        ]);
    }
}
