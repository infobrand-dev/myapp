<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Finance\Http\Requests\StoreFinanceTransactionRequest;
use App\Modules\Finance\Models\FinanceCategory;
use App\Modules\Finance\Models\FinanceTransaction;
use App\Modules\PointOfSale\Models\PosCashSession;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FinanceTransactionController extends Controller
{
    public function index(): View
    {
        $filters = request()->only(['date_from', 'date_to', 'finance_category_id', 'created_by', 'outlet_id', 'transaction_type']);
        $shiftEnabled = $this->shiftEnabled();

        $query = FinanceTransaction::query()
            ->where('tenant_id', TenantContext::currentId())
            ->with(array_filter(['category', 'creator', $shiftEnabled ? 'shift' : null]))
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
            ->when(!empty($filters['outlet_id']), function ($query) use ($filters) {
                $query->where('outlet_id', $filters['outlet_id']);
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
                ->orderBy('transaction_type')
                ->orderBy('name')
                ->get(),
            'users' => User::query()->where('tenant_id', TenantContext::currentId())->orderBy('name')->get(),
            'summary' => [
                'cash_in_total' => $cashInTotal,
                'cash_out_total' => $cashOutTotal,
                'net_cash_flow' => round($cashInTotal - $cashOutTotal, 2),
            ],
            'shiftEnabled' => $shiftEnabled,
        ]);
    }

    public function create(): View
    {
        $shiftEnabled = $this->shiftEnabled();

        return view('finance::transactions.create', [
            'categories' => FinanceCategory::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('is_active', true)
                ->orderBy('transaction_type')
                ->orderBy('name')
                ->get(),
            'shifts' => $shiftEnabled
                ? PosCashSession::query()->where('tenant_id', TenantContext::currentId())->latest('opened_at')->limit(30)->get()
                : collect(),
            'transactionTypeOptions' => [
                FinanceTransaction::TYPE_CASH_IN => 'Cash In',
                FinanceTransaction::TYPE_CASH_OUT => 'Cash Out',
                FinanceTransaction::TYPE_EXPENSE => 'Expense',
            ],
            'shiftEnabled' => $shiftEnabled,
        ]);
    }

    public function store(StoreFinanceTransactionRequest $request): RedirectResponse
    {
        $transaction = DB::transaction(function () use ($request) {
            return FinanceTransaction::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'transaction_number' => 'FIN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'transaction_type' => $request->input('transaction_type'),
                'transaction_date' => $request->input('transaction_date'),
                'amount' => $request->input('amount'),
                'finance_category_id' => $request->input('finance_category_id'),
                'notes' => $request->input('notes'),
                'outlet_id' => $request->input('outlet_id'),
                'pos_cash_session_id' => $request->input('pos_cash_session_id'),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
                'meta' => [
                    'source_module' => 'finance',
                    'future_accounting_note' => 'Belum diposting ke journal/ledger.',
                ],
            ]);
        });

        return redirect()->route('finance.transactions.show', $transaction)->with('status', 'Finance transaction berhasil dicatat.');
    }

    public function show(FinanceTransaction $transaction): View
    {
        $shiftEnabled = $this->shiftEnabled();

        return view('finance::transactions.show', [
            'transaction' => $transaction->load(array_filter(['category', 'creator', 'updater', $shiftEnabled ? 'shift' : null])),
            'shiftEnabled' => $shiftEnabled,
        ]);
    }

    private function shiftEnabled(): bool
    {
        return Schema::hasTable('pos_cash_sessions');
    }
}
