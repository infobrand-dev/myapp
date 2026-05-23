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
        $usage = $this->accountUsageSummary($tenantId, $companyId);

        return view('finance::chart-of-accounts.index', [
            'accounts' => ChartOfAccount::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->with('parent')
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get(),
            'usageSummary' => $usage,
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
        $usage = $this->accountUsageSummary(TenantContext::currentId(), CompanyContext::currentId());

        return view('finance::chart-of-accounts.edit', [
            'account' => $chartOfAccount,
            'accountUsage' => $usage[$chartOfAccount->code] ?? ['journal_count' => 0, 'tax_rate_count' => 0],
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
        $usage = $this->accountUsageSummary(TenantContext::currentId(), CompanyContext::currentId());
        $accountUsage = $usage[$chartOfAccount->code] ?? ['journal_count' => 0, 'tax_rate_count' => 0];
        $isGoverned = $this->accountHasGovernedUsage($accountUsage);

        if ($isGoverned && !$request->boolean('is_active', true)) {
            return redirect()->back()->withInput()->withErrors([
                'is_active' => 'Akun COA yang sudah dipakai journal atau tax master tidak boleh dinonaktifkan.',
            ]);
        }

        if ($isGoverned && !$request->boolean('is_postable', true)) {
            return redirect()->back()->withInput()->withErrors([
                'is_postable' => 'Akun COA yang sudah dipakai posting atau tax master tidak boleh diubah menjadi header/non-postable.',
            ]);
        }

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

        $usage = $this->accountUsageSummary(TenantContext::currentId(), CompanyContext::currentId());
        $accountUsage = $usage[$chartOfAccount->code] ?? ['journal_count' => 0, 'tax_rate_count' => 0];

        if ($this->accountHasGovernedUsage($accountUsage)) {
            return redirect()->route('finance.chart-accounts.index')->with('error', 'Tidak bisa dihapus karena account code sudah dipakai journal atau tax master.');
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

    private function accountUsageSummary(int $tenantId, int $companyId): array
    {
        $journalCounts = DB::table('accounting_journal_lines')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->selectRaw('account_code, COUNT(*) as aggregate_count')
            ->groupBy('account_code')
            ->pluck('aggregate_count', 'account_code');

        $taxRateCounts = DB::table('finance_tax_rates')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->selectRaw('sales_account_code as account_code, COUNT(*) as aggregate_count')
            ->whereNotNull('sales_account_code')
            ->groupBy('sales_account_code')
            ->pluck('aggregate_count', 'account_code');

        $purchaseTaxCounts = DB::table('finance_tax_rates')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->selectRaw('purchase_account_code as account_code, COUNT(*) as aggregate_count')
            ->whereNotNull('purchase_account_code')
            ->groupBy('purchase_account_code')
            ->pluck('aggregate_count', 'account_code');

        $withholdingTaxCounts = DB::table('finance_tax_rates')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->selectRaw('withholding_account_code as account_code, COUNT(*) as aggregate_count')
            ->whereNotNull('withholding_account_code')
            ->groupBy('withholding_account_code')
            ->pluck('aggregate_count', 'account_code');

        return collect([$journalCounts, $taxRateCounts, $purchaseTaxCounts, $withholdingTaxCounts])
            ->flatMap(fn ($items) => collect($items)->keys())
            ->unique()
            ->mapWithKeys(function ($accountCode) use ($journalCounts, $taxRateCounts, $purchaseTaxCounts, $withholdingTaxCounts) {
                return [
                    $accountCode => [
                        'journal_count' => (int) ($journalCounts[$accountCode] ?? 0),
                        'tax_rate_count' => (int) (($taxRateCounts[$accountCode] ?? 0) + ($purchaseTaxCounts[$accountCode] ?? 0) + ($withholdingTaxCounts[$accountCode] ?? 0)),
                    ],
                ];
            })
            ->all();
    }

    private function accountHasGovernedUsage(array $usage): bool
    {
        return ((int) ($usage['journal_count'] ?? 0)) > 0
            || ((int) ($usage['tax_rate_count'] ?? 0)) > 0;
    }
}
