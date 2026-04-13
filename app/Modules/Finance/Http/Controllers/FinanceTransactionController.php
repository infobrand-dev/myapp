<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Finance\Models\FinanceAccount;
use App\Modules\Finance\Http\Requests\StoreFinanceTransactionRequest;
use App\Modules\Finance\Http\Requests\UpdateFinanceTransactionRequest;
use App\Modules\Finance\Models\FinanceCategory;
use App\Modules\Finance\Models\FinanceTransaction;
use App\Modules\Finance\Services\FinanceAccountProvisioner;
use App\Modules\PointOfSale\Models\PosCashSession;
use App\Support\AccountingPeriodLockService;
use App\Support\BooleanQuery;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\SensitiveActionApprovalService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceTransactionController extends Controller
{
    public function index(FinanceAccountProvisioner $accountProvisioner): View
    {
        $companyId = $this->requireCurrentCompanyId();
        $accountProvisioner->ensureDefaults(TenantContext::currentId(), $companyId, auth()->id());
        $filters = request()->only(['date_from', 'date_to', 'finance_account_id', 'finance_category_id', 'created_by', 'branch_id', 'transaction_type']);
        $shiftEnabled = $this->shiftEnabled();
        $company = CompanyContext::currentCompany();

        $query = FinanceTransaction::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', $companyId)
            ->with(array_filter(['account', 'category', 'branch', 'creator', 'counterpartyAccount', $shiftEnabled ? 'shift' : null]))
            ->when(empty($filters['branch_id']), fn ($builder) => BranchContext::applyScope($builder))
            ->when(!empty($filters['date_from']), function ($query) use ($filters) {
                $query->whereDate('transaction_date', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function ($query) use ($filters) {
                $query->whereDate('transaction_date', '<=', $filters['date_to']);
            })
            ->when(!empty($filters['finance_account_id']), function ($query) use ($filters) {
                $query->where('finance_account_id', $filters['finance_account_id']);
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
            ->whereNull('transfer_group_key')
            ->sum('amount'), 2);

        $cashOutTotal = round((float) (clone $summaryQuery)
            ->whereIn('transaction_type', [FinanceTransaction::TYPE_CASH_OUT, FinanceTransaction::TYPE_EXPENSE])
            ->whereNull('transfer_group_key')
            ->sum('amount'), 2);

        return view('finance::transactions.index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'categories' => FinanceCategory::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->orderBy('transaction_type')
                ->orderBy('name')
                ->get(),
            'accounts' => FinanceAccount::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->orderByDesc('is_default')
                ->orderBy('account_type')
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

    public function cashbook(FinanceAccountProvisioner $accountProvisioner): View
    {
        $companyId = $this->requireCurrentCompanyId();
        $accountProvisioner->ensureDefaults(TenantContext::currentId(), $companyId, auth()->id());
        $filters = request()->only(['date_from', 'date_to', 'finance_account_id', 'branch_id']);
        $selectedAccountId = $filters['finance_account_id'] ?? null;

        $accounts = FinanceAccount::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', $companyId)
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $account = $selectedAccountId
            ? $accounts->firstWhere('id', (int) $selectedAccountId)
            : $accounts->first();

        $rows = collect();
        $openingBalance = 0.0;
        $closingBalance = 0.0;

        if ($account) {
            $dateFrom = !empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->startOfDay() : null;
            $dateTo = !empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->endOfDay() : null;

            $baseQuery = FinanceTransaction::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->where('finance_account_id', $account->id)
                ->with(['category', 'branch', 'counterpartyAccount']);

            if (!empty($filters['branch_id'])) {
                $baseQuery->where('branch_id', $filters['branch_id']);
            } else {
                BranchContext::applyScope($baseQuery);
            }

            if ($dateTo) {
                $baseQuery->where('transaction_date', '<=', $dateTo);
            }

            $transactions = $baseQuery
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            $openingBalance = (float) $account->opening_balance;

            if ($account->opening_balance_date && $dateFrom && $account->opening_balance_date->gt($dateFrom->toDateString())) {
                $openingBalance = 0.0;
            }

            $prePeriodDelta = $transactions
                ->filter(fn (FinanceTransaction $transaction) => !$dateFrom || $transaction->transaction_date->lt($dateFrom))
                ->sum(fn (FinanceTransaction $transaction) => $transaction->transaction_type === FinanceTransaction::TYPE_CASH_IN ? (float) $transaction->amount : (float) (-1 * $transaction->amount));

            $runningBalance = round($openingBalance + (float) $prePeriodDelta, 2);
            $openingBalance = $runningBalance;

            $rows = $transactions
                ->filter(fn (FinanceTransaction $transaction) => !$dateFrom || $transaction->transaction_date->gte($dateFrom))
                ->map(function (FinanceTransaction $transaction) use (&$runningBalance) {
                    $debit = $transaction->transaction_type === FinanceTransaction::TYPE_CASH_IN ? (float) $transaction->amount : 0.0;
                    $credit = $transaction->transaction_type === FinanceTransaction::TYPE_CASH_IN ? 0.0 : (float) $transaction->amount;
                    $runningBalance = round($runningBalance + $debit - $credit, 2);
                    $transaction->cashbook_debit = $debit;
                    $transaction->cashbook_credit = $credit;
                    $transaction->cashbook_balance = $runningBalance;

                    return $transaction;
                });

            $closingBalance = $runningBalance;
        }

        return view('finance::transactions.cashbook', [
            'accounts' => $accounts,
            'selectedAccount' => $account,
            'filters' => $filters,
            'entries' => $rows,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'branches' => \App\Models\Branch::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->active()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(FinanceAccountProvisioner $accountProvisioner): View
    {
        $companyId = $this->requireCurrentCompanyId();
        $accountProvisioner->ensureDefaults(TenantContext::currentId(), $companyId, auth()->id());
        $shiftEnabled = $this->shiftEnabled();
        $company = CompanyContext::currentCompany();

        return view('finance::transactions.create', [
            'categories' => BooleanQuery::apply(
                FinanceCategory::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', $companyId),
                'is_active'
            )
                ->orderBy('transaction_type')
                ->orderBy('name')
                ->get(),
            'accounts' => FinanceAccount::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->active()
                ->orderByDesc('is_default')
                ->orderBy('account_type')
                ->orderBy('name')
                ->get(),
            'branches' => \App\Models\Branch::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->active()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'shifts' => $shiftEnabled
                ? PosCashSession::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', $companyId)
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

    public function store(StoreFinanceTransactionRequest $request, AccountingPeriodLockService $periodLockService): RedirectResponse
    {
        $companyId = $this->requireCurrentCompanyId();
        $resolvedBranchId = $this->resolveOperationalBranchId($request);
        $periodLockService->ensureDateOpen($request->input('transaction_date'), $resolvedBranchId, 'create finance transaction');

        $transaction = DB::transaction(function () use ($request, $companyId, $resolvedBranchId) {
            if ($request->input('entry_mode') === FinanceTransaction::ENTRY_MODE_TRANSFER) {
                return $this->storeTransfer($request, $companyId, $resolvedBranchId);
            }

            return $this->storeStandardTransaction($request, $companyId, $resolvedBranchId);
        });

        return redirect()->route('finance.transactions.show', $transaction)->with('status', 'Transaksi dicatat.');
    }

    public function show(FinanceTransaction $transaction): View
    {
        $shiftEnabled = $this->shiftEnabled();

        return view('finance::transactions.show', [
            'transaction' => $transaction->load(array_filter(['account', 'category', 'branch', 'creator', 'updater', 'counterpartyAccount', 'transferPair.account', $shiftEnabled ? 'shift' : null])),
            'activities' => $transaction->activities()->with('causer')->latest()->get(),
            'company' => CompanyContext::currentCompany(),
            'branch' => BranchContext::currentBranch(),
            'shiftEnabled' => $shiftEnabled,
        ]);
    }

    public function edit(FinanceTransaction $transaction, FinanceAccountProvisioner $accountProvisioner): View
    {
        $companyId = $this->requireCurrentCompanyId();
        $accountProvisioner->ensureDefaults(TenantContext::currentId(), $companyId, auth()->id());
        $shiftEnabled = $this->shiftEnabled();
        $company = CompanyContext::currentCompany();

        return view('finance::transactions.edit', [
            'transaction' => $transaction,
            'categories' => BooleanQuery::apply(
                FinanceCategory::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', $companyId),
                'is_active'
            )
                ->orderBy('transaction_type')
                ->orderBy('name')
                ->get(),
            'accounts' => FinanceAccount::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->active()
                ->orderByDesc('is_default')
                ->orderBy('account_type')
                ->orderBy('name')
                ->get(),
            'branches' => \App\Models\Branch::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->active()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'shifts' => $shiftEnabled
                ? PosCashSession::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', $companyId)
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

    public function update(
        FinanceTransaction $transaction,
        UpdateFinanceTransactionRequest $request,
        AccountingPeriodLockService $periodLockService,
        SensitiveActionApprovalService $approvalService
    ): RedirectResponse
    {
        $resolvedBranchId = $request->has('branch_id')
            ? $this->resolveOperationalBranchId($request, $transaction->branch_id)
            : $transaction->branch_id;

        $periodLockService->ensureDateOpen($request->input('transaction_date', $transaction->transaction_date), $resolvedBranchId, 'update finance transaction');
        $approvalService->ensureApprovedOrCreatePending(
            'finance',
            'update_transaction',
            $transaction,
            [
                'amount' => (float) $request->input('amount', $transaction->amount),
                'transaction_type' => $request->input('transaction_type', $transaction->transaction_type),
            ],
            $request->user(),
            'Edit finance transaction'
        );

        DB::transaction(function () use ($transaction, $request, $resolvedBranchId) {
            if ($request->input('entry_mode') === FinanceTransaction::ENTRY_MODE_TRANSFER || $transaction->isTransfer()) {
                $this->updateTransfer($transaction, $request, $resolvedBranchId);
                return;
            }

            $attachmentPath = $transaction->attachment_path;
            if ($request->file('attachment') instanceof UploadedFile) {
                $attachmentPath = $this->storeAttachment($request->file('attachment'));
                if ($transaction->attachment_path) {
                    Storage::disk('public')->delete($transaction->attachment_path);
                }
            }

            $transaction->update([
                'transaction_type' => $request->input('transaction_type'),
                'transaction_date' => $request->input('transaction_date'),
                'amount' => $request->input('amount'),
                'finance_account_id' => $request->input('finance_account_id'),
                'finance_category_id' => $request->input('finance_category_id'),
                'counterparty_finance_account_id' => null,
                'transfer_group_key' => null,
                'transfer_pair_transaction_id' => null,
                'attachment_path' => $attachmentPath,
                'notes' => $request->input('notes'),
                'branch_id' => $resolvedBranchId,
                'pos_cash_session_id' => $request->has('pos_cash_session_id') ? $request->input('pos_cash_session_id') : $transaction->pos_cash_session_id,
                'updated_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('finance.transactions.show', $transaction)->with('success', 'Transaksi diperbarui.');
    }

    public function destroy(
        FinanceTransaction $transaction,
        AccountingPeriodLockService $periodLockService,
        SensitiveActionApprovalService $approvalService
    ): RedirectResponse
    {
        $periodLockService->ensureDateOpen($transaction->transaction_date, $transaction->branch_id, 'delete finance transaction');
        $approvalService->ensureApprovedOrCreatePending(
            'finance',
            'delete_transaction',
            $transaction,
            ['amount' => (float) $transaction->amount, 'transaction_type' => $transaction->transaction_type],
            request()->user(),
            'Delete finance transaction'
        );

        $attachmentPath = $transaction->attachment_path;

        if ($transaction->isTransfer() && $transaction->transferPair) {
            $pair = $transaction->transferPair;
            $pair->delete();
        }

        if ($attachmentPath) {
            Storage::disk('public')->delete($attachmentPath);
        }

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
        $companyId = $this->requireCurrentCompanyId();

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

    private function requireCurrentCompanyId(): int
    {
        $companyId = CompanyContext::currentId();

        if ($companyId) {
            return (int) $companyId;
        }

        throw ValidationException::withMessages([
            'company' => 'Pilih company aktif terlebih dahulu sebelum mengelola transaksi finance.',
        ]);
    }

    private function resolveOperationalBranchId($request, ?int $fallbackBranchId = null): ?int
    {
        $branchId = $request->input('branch_id');

        if ($branchId !== null && $branchId !== '') {
            return (int) $branchId;
        }

        return $fallbackBranchId ?: BranchContext::currentOrDefaultId($request->user(), CompanyContext::currentId());
    }

    private function storeStandardTransaction(StoreFinanceTransactionRequest $request, int $companyId, ?int $resolvedBranchId): FinanceTransaction
    {
        return FinanceTransaction::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => $companyId,
            'transaction_number' => $this->generateTransactionNumber(),
            'transaction_type' => $request->input('transaction_type'),
            'transaction_date' => $request->input('transaction_date'),
            'amount' => $request->input('amount'),
            'finance_account_id' => $request->input('finance_account_id'),
            'finance_category_id' => $request->input('finance_category_id'),
            'attachment_path' => $request->file('attachment') instanceof UploadedFile ? $this->storeAttachment($request->file('attachment')) : null,
            'notes' => $request->input('notes'),
            'branch_id' => $resolvedBranchId,
            'pos_cash_session_id' => $request->input('pos_cash_session_id'),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
            'meta' => [
                'source_module' => 'finance',
                'future_accounting_note' => 'Belum diposting ke journal/ledger.',
            ],
        ]);
    }

    private function storeTransfer(StoreFinanceTransactionRequest $request, int $companyId, ?int $resolvedBranchId): FinanceTransaction
    {
        $groupKey = (string) Str::ulid();
        $attachmentPath = $request->file('attachment') instanceof UploadedFile ? $this->storeAttachment($request->file('attachment')) : null;
        $outCategoryId = $this->ensureTransferCategory(FinanceTransaction::TYPE_CASH_OUT, $request->user()->id, $companyId)->id;
        $inCategoryId = $this->ensureTransferCategory(FinanceTransaction::TYPE_CASH_IN, $request->user()->id, $companyId)->id;

        $source = FinanceTransaction::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => $companyId,
            'transaction_number' => $this->generateTransactionNumber(),
            'transaction_type' => FinanceTransaction::TYPE_CASH_OUT,
            'transaction_date' => $request->input('transaction_date'),
            'amount' => $request->input('amount'),
            'finance_account_id' => $request->input('finance_account_id'),
            'finance_category_id' => $outCategoryId,
            'counterparty_finance_account_id' => $request->input('counterparty_finance_account_id'),
            'transfer_group_key' => $groupKey,
            'attachment_path' => $attachmentPath,
            'notes' => $request->input('notes'),
            'branch_id' => $resolvedBranchId,
            'pos_cash_session_id' => $request->input('pos_cash_session_id'),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
            'meta' => [
                'source_module' => 'finance',
                'future_accounting_note' => 'Belum diposting ke journal/ledger.',
                'entry_mode' => FinanceTransaction::ENTRY_MODE_TRANSFER,
                'transfer_direction' => 'out',
            ],
        ]);

        $target = FinanceTransaction::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => $companyId,
            'transaction_number' => $this->generateTransactionNumber(),
            'transaction_type' => FinanceTransaction::TYPE_CASH_IN,
            'transaction_date' => $request->input('transaction_date'),
            'amount' => $request->input('amount'),
            'finance_account_id' => $request->input('counterparty_finance_account_id'),
            'finance_category_id' => $inCategoryId,
            'counterparty_finance_account_id' => $request->input('finance_account_id'),
            'transfer_group_key' => $groupKey,
            'attachment_path' => $attachmentPath,
            'notes' => $request->input('notes'),
            'branch_id' => $resolvedBranchId,
            'pos_cash_session_id' => $request->input('pos_cash_session_id'),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
            'meta' => [
                'source_module' => 'finance',
                'future_accounting_note' => 'Belum diposting ke journal/ledger.',
                'entry_mode' => FinanceTransaction::ENTRY_MODE_TRANSFER,
                'transfer_direction' => 'in',
            ],
        ]);

        $source->update(['transfer_pair_transaction_id' => $target->id]);
        $target->update(['transfer_pair_transaction_id' => $source->id]);

        return $source;
    }

    private function updateTransfer(FinanceTransaction $transaction, UpdateFinanceTransactionRequest $request, ?int $resolvedBranchId): void
    {
        $source = (($transaction->meta['transfer_direction'] ?? 'out') === 'out')
            ? $transaction
            : $transaction->transferPair;

        $target = $source?->transferPair;

        if (!$source) {
            throw ValidationException::withMessages([
                'transaction' => 'Transfer pasangan tidak ditemukan.',
            ]);
        }

        $companyId = $this->requireCurrentCompanyId();
        $outCategoryId = $this->ensureTransferCategory(FinanceTransaction::TYPE_CASH_OUT, $request->user()->id, $companyId)->id;
        $inCategoryId = $this->ensureTransferCategory(FinanceTransaction::TYPE_CASH_IN, $request->user()->id, $companyId)->id;
        $attachmentPath = $source->attachment_path;

        if ($request->file('attachment') instanceof UploadedFile) {
            $attachmentPath = $this->storeAttachment($request->file('attachment'));
            if ($source->attachment_path) {
                Storage::disk('public')->delete($source->attachment_path);
            }
        }

        if (!$target) {
            throw ValidationException::withMessages([
                'transaction' => 'Transfer pasangan tidak ditemukan.',
            ]);
        }

        $groupKey = $source->transfer_group_key ?: (string) Str::ulid();

        $source->update([
            'transaction_type' => FinanceTransaction::TYPE_CASH_OUT,
            'transaction_date' => $request->input('transaction_date'),
            'amount' => $request->input('amount'),
            'finance_account_id' => $request->input('finance_account_id'),
            'finance_category_id' => $outCategoryId,
            'counterparty_finance_account_id' => $request->input('counterparty_finance_account_id'),
            'transfer_group_key' => $groupKey,
            'attachment_path' => $attachmentPath,
            'notes' => $request->input('notes'),
            'branch_id' => $resolvedBranchId,
            'pos_cash_session_id' => $request->has('pos_cash_session_id') ? $request->input('pos_cash_session_id') : $source->pos_cash_session_id,
            'updated_by' => $request->user()->id,
            'meta' => array_merge($source->meta ?? [], [
                'entry_mode' => FinanceTransaction::ENTRY_MODE_TRANSFER,
                'transfer_direction' => 'out',
            ]),
        ]);

        $target->update([
            'transaction_type' => FinanceTransaction::TYPE_CASH_IN,
            'transaction_date' => $request->input('transaction_date'),
            'amount' => $request->input('amount'),
            'finance_account_id' => $request->input('counterparty_finance_account_id'),
            'finance_category_id' => $inCategoryId,
            'counterparty_finance_account_id' => $request->input('finance_account_id'),
            'transfer_group_key' => $groupKey,
            'attachment_path' => $attachmentPath,
            'notes' => $request->input('notes'),
            'branch_id' => $resolvedBranchId,
            'pos_cash_session_id' => $request->has('pos_cash_session_id') ? $request->input('pos_cash_session_id') : $target->pos_cash_session_id,
            'updated_by' => $request->user()->id,
            'meta' => array_merge($target->meta ?? [], [
                'entry_mode' => FinanceTransaction::ENTRY_MODE_TRANSFER,
                'transfer_direction' => 'in',
            ]),
        ]);

        $source->update(['transfer_pair_transaction_id' => $target->id]);
        $target->update(['transfer_pair_transaction_id' => $source->id]);
    }

    private function ensureTransferCategory(string $type, int $userId, int $companyId): FinanceCategory
    {
        $name = $type === FinanceTransaction::TYPE_CASH_IN ? 'Transfer Masuk' : 'Transfer Keluar';
        $slug = $type === FinanceTransaction::TYPE_CASH_IN ? 'system-transfer-masuk' : 'system-transfer-keluar';

        return FinanceCategory::query()->firstOrCreate(
            [
                'tenant_id' => TenantContext::currentId(),
                'company_id' => $companyId,
                'slug' => $slug,
            ],
            [
                'name' => $name,
                'transaction_type' => $type,
                'is_active' => true,
                'notes' => 'Kategori sistem untuk transfer antar account.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function storeAttachment(UploadedFile $file): string
    {
        return $file->store('finance/attachments', 'public');
    }
}
