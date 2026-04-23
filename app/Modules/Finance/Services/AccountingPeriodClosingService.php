<?php

namespace App\Modules\Finance\Services;

use App\Models\AccountingPeriodClosing;
use App\Models\User;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingPeriodClosingService
{
    private $journalService;
    private $periodLockService;

    public function __construct(AccountingJournalService $journalService, AccountingPeriodLockService $periodLockService)
    {
        $this->journalService = $journalService;
        $this->periodLockService = $periodLockService;
    }

    public function close(array $data, ?User $actor = null): AccountingPeriodClosing
    {
        $periodStart = Carbon::parse($data['period_start'])->startOfDay();
        $periodEnd = Carbon::parse($data['period_end'])->endOfDay();
        $branchId = $data['branch_id'] ?? null;
        $scopeKey = $branchId ? ('branch:' . $branchId) : 'company';
        $notes = $data['notes'] ?? null;

        $this->ensurePeriodOpen($periodStart, $periodEnd, $branchId);

        return DB::transaction(function () use ($periodStart, $periodEnd, $branchId, $scopeKey, $notes, $actor) {
            $existing = AccountingPeriodClosing::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->whereDate('period_start', $periodStart->toDateString())
                ->whereDate('period_end', $periodEnd->toDateString())
                ->where(function ($query) use ($scopeKey) {
                    if ($scopeKey === 'company') {
                        $query->where('closing_scope_key', 'company')
                            ->orWhere('closing_scope_key', 'like', 'branch:%');
                    } else {
                        $query->where('closing_scope_key', 'company')
                            ->orWhere('closing_scope_key', $scopeKey);
                    }
                })
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'period_start' => 'Periode ini sudah pernah di-closing untuk scope yang sama.',
                ]);
            }

            $summary = $this->closingSummary($periodStart, $periodEnd, $branchId);

            if (empty($summary['lines'])) {
                throw ValidationException::withMessages([
                    'period_start' => 'Tidak ada akun laba/rugi posted yang perlu di-closing pada periode ini.',
                ]);
            }

            $closing = AccountingPeriodClosing::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $branchId,
                'closing_scope_key' => $scopeKey,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'status' => 'closed',
                'revenue_total' => $summary['revenue_total'],
                'expense_total' => $summary['expense_total'],
                'net_income' => $summary['net_income'],
                'retained_earnings_account_code' => $summary['retained_earnings']['account_code'],
                'retained_earnings_account_name' => $summary['retained_earnings']['account_name'],
                'closed_by' => $actor ? $actor->id : null,
                'closed_at' => now(),
                'notes' => $notes,
                'meta' => [
                    'closing_scope_key' => $scopeKey,
                    'nominal_accounts' => $summary['nominal_accounts'],
                ],
            ]);

            $journal = $this->journalService->sync(
                $closing,
                'period_closing',
                $periodEnd,
                $summary['lines'],
                [
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'closing_scope_key' => $scopeKey,
                    'net_income' => $summary['net_income'],
                    'notes' => $notes,
                ],
                'Period closing ' . $periodStart->toDateString() . ' s/d ' . $periodEnd->toDateString()
            );

            $lock = $this->periodLockService->create([
                'branch_id' => $branchId,
                'locked_from' => $periodStart->toDateString(),
                'locked_until' => $periodEnd->toDateString(),
                'notes' => 'Auto lock dari period closing #' . $closing->id . ($notes ? ': ' . $notes : ''),
            ], $actor);

            $closing->update([
                'closing_journal_id' => $journal->id,
                'period_lock_id' => $lock->id,
            ]);

            return $closing->fresh(['closingJournal', 'periodLock', 'closer']);
        });
    }

    public function closingSummary(Carbon $periodStart, Carbon $periodEnd, ?int $branchId = null): array
    {
        $retainedEarnings = $this->retainedEarningsAccount();
        $nominalAccounts = $this->nominalAccountRows($periodStart, $periodEnd, $branchId);
        $lines = [];
        $revenueTotal = 0.0;
        $expenseTotal = 0.0;

        foreach ($nominalAccounts as $account) {
            $balance = round((float) $account['balance'], 2);

            if ($account['account_type'] === ChartOfAccount::TYPE_REVENUE) {
                $revenueTotal = round($revenueTotal + $balance, 2);

                if ($balance > 0) {
                    $lines[] = $this->journalLine($account, $balance, 0.0);
                } elseif ($balance < 0) {
                    $lines[] = $this->journalLine($account, 0.0, abs($balance));
                }
            }

            if ($account['account_type'] === ChartOfAccount::TYPE_EXPENSE) {
                $expenseTotal = round($expenseTotal + $balance, 2);

                if ($balance > 0) {
                    $lines[] = $this->journalLine($account, 0.0, $balance);
                } elseif ($balance < 0) {
                    $lines[] = $this->journalLine($account, abs($balance), 0.0);
                }
            }
        }

        $netIncome = round($revenueTotal - $expenseTotal, 2);

        if ($netIncome > 0) {
            $lines[] = [
                'account_code' => $retainedEarnings['account_code'],
                'account_name' => $retainedEarnings['account_name'],
                'debit' => 0.0,
                'credit' => $netIncome,
                'meta' => ['closing_role' => 'retained_earnings'],
            ];
        } elseif ($netIncome < 0) {
            $lines[] = [
                'account_code' => $retainedEarnings['account_code'],
                'account_name' => $retainedEarnings['account_name'],
                'debit' => abs($netIncome),
                'credit' => 0.0,
                'meta' => ['closing_role' => 'retained_earnings'],
            ];
        }

        return [
            'revenue_total' => $revenueTotal,
            'expense_total' => $expenseTotal,
            'net_income' => $netIncome,
            'retained_earnings' => $retainedEarnings,
            'nominal_accounts' => $nominalAccounts,
            'lines' => $lines,
        ];
    }

    private function nominalAccountRows(Carbon $periodStart, Carbon $periodEnd, ?int $branchId = null): array
    {
        $rows = DB::table('accounting_journal_lines')
            ->join('accounting_journals', 'accounting_journals.id', '=', 'accounting_journal_lines.journal_id')
            ->leftJoin('chart_of_accounts', function ($join) {
                $join->on('chart_of_accounts.code', '=', 'accounting_journal_lines.account_code')
                    ->on('chart_of_accounts.tenant_id', '=', 'accounting_journals.tenant_id')
                    ->on('chart_of_accounts.company_id', '=', 'accounting_journals.company_id');
            })
            ->where('accounting_journals.tenant_id', TenantContext::currentId())
            ->where('accounting_journals.company_id', CompanyContext::currentId())
            ->where('accounting_journals.status', AccountingJournalService::STATUS_POSTED)
            ->where('accounting_journals.entry_type', '!=', 'period_closing')
            ->whereBetween('accounting_journals.entry_date', [$periodStart, $periodEnd])
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('accounting_journals.branch_id', $branchId);
            })
            ->selectRaw('accounting_journal_lines.account_code')
            ->selectRaw('MAX(accounting_journal_lines.account_name) as account_name')
            ->selectRaw('MAX(chart_of_accounts.account_type) as coa_account_type')
            ->selectRaw('MAX(chart_of_accounts.report_section) as coa_report_section')
            ->selectRaw('SUM(accounting_journal_lines.debit) as debit_total')
            ->selectRaw('SUM(accounting_journal_lines.credit) as credit_total')
            ->groupBy('accounting_journal_lines.account_code')
            ->orderBy('accounting_journal_lines.account_code')
            ->get();

        return $rows->map(function ($row) {
            $accountType = $this->nominalAccountType($row);

            if (!$accountType) {
                return null;
            }

            $debit = round((float) $row->debit_total, 2);
            $credit = round((float) $row->credit_total, 2);
            $balance = $accountType === ChartOfAccount::TYPE_REVENUE
                ? round($credit - $debit, 2)
                : round($debit - $credit, 2);

            if ($balance === 0.0) {
                return null;
            }

            return [
                'account_code' => (string) $row->account_code,
                'account_name' => (string) $row->account_name,
                'account_type' => $accountType,
                'debit_total' => $debit,
                'credit_total' => $credit,
                'balance' => $balance,
            ];
        })->filter()->values()->all();
    }

    private function ensurePeriodOpen(Carbon $periodStart, Carbon $periodEnd, ?int $branchId = null): void
    {
        $query = \App\Models\AccountingPeriodLock::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('status', 'active')
            ->whereDate('locked_from', '<=', $periodEnd->toDateString())
            ->whereDate('locked_until', '>=', $periodStart->toDateString());

        if ($branchId) {
            $query->where(function ($scopeQuery) use ($branchId) {
                $scopeQuery->whereNull('branch_id')->orWhere('branch_id', $branchId);
            });
        }

        $lock = $query->latest('locked_until')->first();

        if (!$lock) {
            return;
        }

        throw ValidationException::withMessages([
            'period_start' => 'Periode yang dipilih overlap dengan period lock aktif ' . $lock->locked_from->format('Y-m-d') . ' s/d ' . $lock->locked_until->format('Y-m-d') . '.',
        ]);
    }

    private function nominalAccountType($row): ?string
    {
        if ($row->coa_report_section === ChartOfAccount::SECTION_PROFIT_LOSS
            && in_array($row->coa_account_type, [ChartOfAccount::TYPE_REVENUE, ChartOfAccount::TYPE_EXPENSE], true)) {
            return $row->coa_account_type;
        }

        $code = strtoupper((string) $row->account_code);
        $name = strtoupper((string) $row->account_name);

        if (in_array($code, ['SALES', 'PURCHASE_DISC', 'INV_ADJ_GAIN'], true)
            || str_starts_with($code, 'REV')
            || str_contains($name, 'REVENUE')
            || str_contains($name, 'INCOME')
            || str_contains($name, 'GAIN')) {
            return ChartOfAccount::TYPE_REVENUE;
        }

        if (in_array($code, ['SALES_DISC', 'SALES_REFUND', 'COGS', 'PURCHASES', 'LANDED_COST', 'INV_ADJ_LOSS'], true)
            || str_starts_with($code, 'EXP')
            || str_contains($name, 'EXPENSE')
            || str_contains($name, 'COST')
            || str_contains($name, 'LOSS')) {
            return ChartOfAccount::TYPE_EXPENSE;
        }

        return null;
    }

    private function retainedEarningsAccount(): array
    {
        $account = ChartOfAccount::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('code', 'RETAINED_EARNINGS')
            ->first();

        if ($account) {
            return [
                'account_code' => $account->code,
                'account_name' => $account->name,
            ];
        }

        return [
            'account_code' => 'RETAINED_EARNINGS',
            'account_name' => 'Retained Earnings',
        ];
    }

    private function journalLine(array $account, float $debit, float $credit): array
    {
        return [
            'account_code' => $account['account_code'],
            'account_name' => $account['account_name'],
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'meta' => [
                'closing_role' => 'nominal_account',
                'account_type' => $account['account_type'],
            ],
        ];
    }
}
