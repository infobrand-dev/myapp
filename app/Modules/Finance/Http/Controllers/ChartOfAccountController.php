<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Requests\StoreChartOfAccountRequest;
use App\Modules\Finance\Http\Requests\UpdateChartOfAccountRequest;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Services\ChartOfAccountProvisioner;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ChartOfAccountController extends Controller
{
    public function index(ChartOfAccountProvisioner $provisioner): View
    {
        $companyId = $this->requireCurrentCompanyId();
        $tenantId = TenantContext::currentId();
        $provisioner->ensureDefaults($tenantId, $companyId, auth()->id());

        return view('finance::chart-of-accounts.index', [
            'accounts' => ChartOfAccount::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->with('parent')
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get(),
            'parentOptions' => ChartOfAccount::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'typeOptions' => ChartOfAccount::typeOptions(),
            'normalBalanceOptions' => ChartOfAccount::normalBalanceOptions(),
            'reportSectionOptions' => ChartOfAccount::reportSectionOptions(),
        ]);
    }

    public function edit(ChartOfAccount $chartOfAccount): View
    {
        return view('finance::chart-of-accounts.edit', [
            'account' => $chartOfAccount,
            'parentOptions' => ChartOfAccount::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->where('id', '!=', $chartOfAccount->id)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'typeOptions' => ChartOfAccount::typeOptions(),
            'normalBalanceOptions' => ChartOfAccount::normalBalanceOptions(),
            'reportSectionOptions' => ChartOfAccount::reportSectionOptions(),
        ]);
    }

    public function store(StoreChartOfAccountRequest $request): RedirectResponse
    {
        ChartOfAccount::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => $this->requireCurrentCompanyId(),
            'parent_id' => $request->integer('parent_id') ?: null,
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'account_type' => $request->input('account_type'),
            'normal_balance' => $request->input('normal_balance'),
            'report_section' => $request->input('report_section'),
            'is_postable' => $request->boolean('is_postable', true),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
            'description' => $request->input('description'),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('finance.chart-accounts.index')->with('status', 'Akun COA ditambahkan.');
    }

    public function update(ChartOfAccount $chartOfAccount, UpdateChartOfAccountRequest $request): RedirectResponse
    {
        $chartOfAccount->update([
            'parent_id' => $request->integer('parent_id') ?: null,
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'account_type' => $request->input('account_type'),
            'normal_balance' => $request->input('normal_balance'),
            'report_section' => $request->input('report_section'),
            'is_postable' => $request->boolean('is_postable', true),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
            'description' => $request->input('description'),
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('finance.chart-accounts.index')->with('status', 'Akun COA diperbarui.');
    }

    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        if ($chartOfAccount->children()->exists()) {
            return redirect()->route('finance.chart-accounts.index')->with('error', 'Tidak bisa dihapus karena masih punya child account.');
        }

        $used = DB::table('accounting_journal_lines')
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('account_code', $chartOfAccount->code)
            ->exists();

        if ($used) {
            return redirect()->route('finance.chart-accounts.index')->with('error', 'Tidak bisa dihapus karena account code sudah dipakai journal.');
        }

        $chartOfAccount->delete();

        return redirect()->route('finance.chart-accounts.index')->with('status', 'Akun COA dihapus.');
    }

    private function requireCurrentCompanyId(): int
    {
        $companyId = CompanyContext::currentId();

        if ($companyId) {
            return (int) $companyId;
        }

        throw ValidationException::withMessages([
            'company' => 'Pilih company aktif terlebih dahulu sebelum mengelola chart of accounts.',
        ]);
    }
}
