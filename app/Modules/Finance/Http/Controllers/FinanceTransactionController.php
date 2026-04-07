<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Finance\Http\Requests\StoreFinanceTransactionRequest;
use App\Modules\Finance\Http\Requests\UpdateFinanceTransactionRequest;
use App\Modules\Finance\Models\FinanceCategory;
use App\Modules\Finance\Models\FinanceTransaction;
use App\Modules\PointOfSale\Models\PosCashSession;
use App\Support\BooleanQuery;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\View\View;

class FinanceTransactionController extends Controller
{
    public function index(): View
    {
        $filters = request()->only(['date_from', 'date_to', 'finance_category_id', 'created_by', 'branch_id', 'transaction_type']);
        $shiftEnabled = $this->shiftEnabled();
        $company = CompanyContext::currentCompany();

        $query = FinanceTransaction::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->with(array_filter(['category', 'creator', $shiftEnabled ? 'shift' : null]))
            ->when(empty($filters['branch_id']), fn ($builder) => BranchContext::applyScope($builder))
            ->when(!empty($filters['date_from']), function ($query) use ($filters) {
                $query->whereDate('transaction_date', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function ($query) use ($filters) {
                $query->whereDate('transaction_date', '<=', $filters['date_to']);
            })
            ->when(!empty($filters['finance_category_id']), function ($query) use ($filters) {
                $query->where('finance_category_id', $filters['finance_category_id']);
            })
            ->when(!empty($filters['created_by']), function ($query) use ($filters) {
                $query->where('created_by', $filters['created_by']);
            })
            ->when(!empty($filters['branch_id']), function ($query) use ($filters) {
                $query->where('branch_id', $filters['branch_id']);
            })
            ->when(!empty($filters['transaction_type']), function ($query) use ($filters) {
                $query->where('transaction_type', $filters['transaction_type']);
            });

        $summaryQuery = clone $query;

        $transactions = $query
            ->latest('transaction_date')
            ->paginate(20)
            ->withQueryString();

        $cashInTotal = round((float) (clone $summaryQuery)
            ->where('transaction_type', FinanceTransaction::TYPE_CASH_IN)
            ->sum('amount'), 2);

        $cashOutTotal = round((float) (clone $summaryQuery)
            ->whereIn('transaction_type', [FinanceTransaction::TYPE_CASH_OUT, FinanceTransaction::TYPE_EXPENSE])
            ->sum('amount'), 2);

        return view('finance::transactions.index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'categories' => FinanceCategory::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->orderBy('transaction_type')
                ->orderBy('name')
                ->get(),
            'users' => User::query()->where('tenant_id', TenantContext::currentId())->orderBy('name')->get(),
            'summary' => [
                'cash_in_total' => $cashInTotal,
                'cash_out_total' => $cashOutTotal,
                'net_cash_flow' => round($cashInTotal - $cashOutTotal, 2),
            ],
            'company' => $company,
            'branch' => BranchContext::currentBranch(),
            'shiftEnabled' => $shiftEnabled,
        ]);
    }

    public function create(): View
    {
        $shiftEnabled = $this->shiftEnabled();
        $company = CompanyContext::currentCompany();

        return view('finance::transactions.create', [
            'categories' => BooleanQuery::apply(
                FinanceCategory::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId()),
                'is_active'
            )
                ->orderBy('transaction_type')
                ->orderBy('name')
                ->get(),
            'shifts' => $shiftEnabled
                ? PosCashSession::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())
                    ->tap(fn ($query) => BranchContext::applyScope($query))
                    ->latest('opened_at')
                    ->limit(30)
                    ->get()
                : collect(),
            'transactionTypeOptions' => [
                FinanceTransaction::TYPE_CASH_IN => 'Cash In',
                FinanceTransaction::TYPE_CASH_OUT => 'Cash Out',
                FinanceTransaction::TYPE_EXPENSE => 'Expense',
            ],
            'company' => $company,
            'branch' => BranchContext::currentBranch(),
            'shiftEnabled' => $shiftEnabled,
        ]);
    }

    public function store(StoreFinanceTransactionRequest $request): RedirectResponse
    {
        $transaction = DB::transaction(function () use ($request) {
            return FinanceTransaction::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'transaction_number' => $this->generateTransactionNumber(),
                'transaction_type' => $request->input('transaction_type'),
                'transaction_date' => $request->input('transaction_date'),
                'amount' => $request->input('amount'),
                'finance_category_id' => $request->input('finance_category_id'),
                'notes' => $request->input('notes'),
                'branch_id' => $request->input('branch_id', BranchContext::currentId()),
                'pos_cash_session_id' => $request->input('pos_cash_session_id'),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
                'meta' => [
                    'source_module' => 'finance',
                    'future_accounting_note' => 'Belum diposting ke journal/ledger.',
                ],
            ]);
        });

        return redirect()->route('finance.transactions.show', $transaction)->with('status', 'Transaksi dicatat.');
    }

    public function show(FinanceTransaction $transaction): View
    {
        $shiftEnabled = $this->shiftEnabled();

        return view('finance::transactions.show', [
            'transaction' => $transaction->load(array_filter(['category', 'creator', 'updater', $shiftEnabled ? 'shift' : null])),
            'company' => CompanyContext::currentCompany(),
            'branch' => BranchContext::currentBranch(),
            'shiftEnabled' => $shiftEnabled,
        ]);
    }

    public function edit(FinanceTransaction $transaction): View
    {
        $shiftEnabled = $this->shiftEnabled();
        $company = CompanyContext::currentCompany();

        return view('finance::transactions.edit', [
            'transaction' => $transaction,
            'categories' => BooleanQuery::apply(
                FinanceCategory::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId()),
                'is_active'
            )
                ->orderBy('transaction_type')
                ->orderBy('name')
                ->get(),
            'shifts' => $shiftEnabled
                ? PosCashSession::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())
                    ->tap(fn ($query) => BranchContext::applyScope($query))
                    ->latest('opened_at')
                    ->limit(30)
                    ->get()
                : collect(),
            'transactionTypeOptions' => [
                FinanceTransaction::TYPE_CASH_IN => 'Cash In',
                FinanceTransaction::TYPE_CASH_OUT => 'Cash Out',
                FinanceTransaction::TYPE_EXPENSE => 'Expense',
            ],
            'company' => $company,
            'branch' => BranchContext::currentBranch(),
            'shiftEnabled' => $shiftEnabled,
        ]);
    }

    public function update(FinanceTransaction $transaction, UpdateFinanceTransactionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($transaction, $request) {
            $transaction->update([
                'transaction_type' => $request->input('transaction_type'),
                'transaction_date' => $request->input('transaction_date'),
                'amount' => $request->input('amount'),
                'finance_category_id' => $request->input('finance_category_id'),
                'notes' => $request->input('notes'),
                'branch_id' => $request->input('branch_id', BranchContext::currentId()),
                'pos_cash_session_id' => $request->input('pos_cash_session_id'),
                'updated_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('finance.transactions.show', $transaction)->with('success', 'Transaksi diperbarui.');
    }

    public function destroy(FinanceTransaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return redirect()->route('finance.transactions.index')->with('success', 'Transaksi dihapus.');
    }

    private function shiftEnabled(): bool
    {
        return Schema::hasTable('pos_cash_sessions');
    }

    private function generateTransactionNumber(): string
    {
        $tenantId = TenantContext::currentId();
        $companyId = (int) CompanyContext::currentId();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $number = 'FIN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));

            try {
                $exists = FinanceTransaction::query()
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->where('transaction_number', $number)
                    ->exists();
            } catch (QueryException) {
                $exists = false;
            }

            if (!$exists) {
                return $number;
            }
        }

        return 'FIN-' . now()->format('YmdHis') . '-' . Str::upper(Str::ulid());
    }
}
