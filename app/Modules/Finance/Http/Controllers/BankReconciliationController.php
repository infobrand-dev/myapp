<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Requests\CompleteBankReconciliationRequest;
use App\Modules\Finance\Http\Requests\ImportBankStatementRequest;
use App\Modules\Finance\Http\Requests\ResolveBankStatementLineRequest;
use App\Modules\Finance\Http\Requests\StoreBankReconciliationRequest;
use App\Modules\Finance\Models\BankReconciliation;
use App\Modules\Finance\Models\BankReconciliationItem;
use App\Modules\Finance\Models\FinanceAccount;
use App\Modules\Finance\Models\FinanceTransaction;
use App\Modules\Finance\Models\BankStatementImport;
use App\Modules\Finance\Models\BankStatementLine;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Support\SimpleSpreadsheet;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BankReconciliationController extends Controller
{
    public function index(): View
    {
        $companyId = (int) CompanyContext::currentId();

        return view('finance::reconciliations.index', [
            'reconciliations' => BankReconciliation::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->with(['account', 'branch', 'creator', 'completer'])
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->latest('period_end')
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'accounts' => FinanceAccount::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->active()
                ->orderByDesc('is_default')
                ->orderBy('account_type')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreBankReconciliationRequest $request): RedirectResponse
    {
        $companyId = (int) CompanyContext::currentId();
        $accountId = (int) $request->input('finance_account_id');
        $periodStart = Carbon::parse($request->input('period_start'))->startOfDay();
        $periodEnd = Carbon::parse($request->input('period_end'))->endOfDay();
        $statementEndingBalance = round((float) $request->input('statement_ending_balance'), 2);
        $bookClosingBalance = $this->bookClosingBalance($accountId, $periodEnd, $request->input('branch_id'));

        $reconciliation = BankReconciliation::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => $companyId,
            'branch_id' => BranchContext::currentId(),
            'finance_account_id' => $accountId,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'statement_ending_balance' => $statementEndingBalance,
            'book_closing_balance' => $bookClosingBalance,
            'difference_amount' => round($statementEndingBalance - $bookClosingBalance, 2),
            'status' => BankReconciliation::STATUS_DRAFT,
            'notes' => $request->input('notes'),
            'created_by' => optional($request->user())->id,
            'updated_by' => optional($request->user())->id,
        ]);

        return redirect()->route('finance.reconciliations.show', $reconciliation)->with('status', 'Sesi reconciliation dibuat.');
    }

    public function show(BankReconciliation $reconciliation): View
    {
        $reconciliation->load([
            'account',
            'branch',
            'creator',
            'completer',
            'items.reconcilable',
            'statementImports.creator',
            'statementLines.importBatch',
            'statementLines.suggestedReconcilable',
            'statementLines.matchedReconcilable',
        ]);

        $candidatePayments = $this->candidatePayments($reconciliation)->get();
        $candidateFinanceTransactions = $this->candidateFinanceTransactions($reconciliation)->get();
        $selectedPaymentIds = $reconciliation->items
            ->where('reconcilable_type', Payment::class)
            ->pluck('reconcilable_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('finance::reconciliations.show', [
            'reconciliation' => $reconciliation,
            'candidatePayments' => $candidatePayments,
            'candidateFinanceTransactions' => $candidateFinanceTransactions,
            'selectedPaymentIds' => $selectedPaymentIds,
            'candidateTotal' => round((float) $candidatePayments->sum('amount'), 2),
            'selectedTotal' => round((float) $reconciliation->items->sum('cleared_amount'), 2),
            'statementLines' => $reconciliation->statementLines,
            'matchedStatementLineIds' => $reconciliation->statementLines
                ->where('match_status', BankStatementLine::MATCH_STATUS_SUGGESTED)
                ->pluck('id')
                ->map(function ($id) { return (int) $id; })
                ->all(),
        ]);
    }

    public function importStatement(ImportBankStatementRequest $request, BankReconciliation $reconciliation): RedirectResponse
    {
        if ($reconciliation->status !== BankReconciliation::STATUS_DRAFT) {
            return redirect()->route('finance.reconciliations.show', $reconciliation)->with('error', 'Statement hanya bisa diimport saat reconciliation masih draft.');
        }

        $file = $request->validated()['import_file'];
        $rows = SimpleSpreadsheet::parseUploadedFile($file->getRealPath(), (string) $file->getClientOriginalExtension());

        if (count($rows) < 2) {
            return redirect()->route('finance.reconciliations.show', $reconciliation)->withErrors([
                'import_file' => 'File statement harus berisi header dan minimal satu baris data.',
            ]);
        }

        $header = collect(array_shift($rows))
            ->map(function ($value) {
                return Str::of((string) $value)->lower()->replace(' ', '_')->replace('-', '_')->toString();
            })
            ->values();

        if (!$this->headerContainsAny($header, ['transaction_date', 'date', 'posting_date', 'book_date', 'value_date'])
            || !$this->headerContainsAny($header, ['amount', 'debit', 'credit', 'debit_amount', 'credit_amount'])) {
            return redirect()->route('finance.reconciliations.show', $reconciliation)->withErrors([
                'import_file' => 'Header wajib minimal punya tanggal transaksi dan nilai amount atau pasangan debit/credit.',
            ]);
        }

        $importedCount = 0;
        $fileHash = hash_file('sha256', $file->getRealPath());

        DB::transaction(function () use ($request, $reconciliation, $file, $rows, $header, $fileHash, &$importedCount) {
            $importBatch = BankStatementImport::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => (int) CompanyContext::currentId(),
                'bank_reconciliation_id' => $reconciliation->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $this->storeStatementFile($file),
                'file_hash' => $fileHash,
                'imported_rows' => 0,
                'created_by' => optional($request->user())->id,
            ]);

            foreach ($rows as $rowIndex => $row) {
                $payload = $header->mapWithKeys(function ($key, $index) use ($row) {
                    return [$key => $row[$index] ?? null];
                });

                $parsedAmount = $this->extractStatementAmount($payload);

                if ($parsedAmount === null || round(abs($parsedAmount), 2) === 0.0) {
                    continue;
                }

                $transactionDate = $this->extractStatementDate($payload);
                $referenceNumber = $this->normalizeNullableString($this->firstPayloadValue($payload, [
                    'reference_number',
                    'reference',
                    'ref_no',
                    'ref_number',
                    'bank_reference',
                    'document_number',
                ]));
                $description = $this->normalizeNullableString($this->firstPayloadValue($payload, [
                    'description',
                    'memo',
                    'notes',
                    'remark',
                    'remarks',
                    'narration',
                ]));
                $externalKey = $this->normalizeNullableString($this->firstPayloadValue($payload, [
                    'external_key',
                    'bank_reference',
                    'reference_number',
                    'transaction_id',
                    'unique_reference',
                ]));
                $direction = $parsedAmount >= 0 ? 'in' : 'out';
                $normalizedAmount = round(abs($parsedAmount), 2);
                $duplicate = $this->findDuplicateStatementLine(
                    $reconciliation,
                    $transactionDate,
                    $normalizedAmount,
                    $direction,
                    $referenceNumber,
                    $externalKey
                );
                $suggestion = $this->suggestReconcilableMatch($reconciliation, $normalizedAmount, $direction, $transactionDate, $referenceNumber, $description);

                BankStatementLine::query()->create([
                    'tenant_id' => TenantContext::currentId(),
                    'company_id' => (int) CompanyContext::currentId(),
                    'bank_reconciliation_id' => $reconciliation->id,
                    'bank_statement_import_id' => $importBatch->id,
                    'transaction_date' => $transactionDate,
                    'direction' => $direction,
                    'amount' => $normalizedAmount,
                    'reference_number' => $referenceNumber,
                    'description' => $description,
                    'external_key' => $externalKey ?: ('row-' . ($rowIndex + 2)),
                    'match_status' => $suggestion ? BankStatementLine::MATCH_STATUS_SUGGESTED : BankStatementLine::MATCH_STATUS_UNMATCHED,
                    'suggested_reconcilable_type' => $suggestion ? $suggestion['type'] : null,
                    'suggested_reconcilable_id' => $suggestion ? $suggestion['id'] : null,
                    'match_score' => $suggestion ? $suggestion['score'] : 0,
                    'matched_reconcilable_type' => null,
                    'matched_reconcilable_id' => null,
                    'meta' => [
                        'import_header' => $header->all(),
                        'raw_row' => $payload->all(),
                        'suggestion_reason' => $suggestion ? $suggestion['reason'] : null,
                        'suggestion_target' => $suggestion ? $suggestion['target'] : null,
                        'duplicate_candidate' => $duplicate ? [
                            'statement_line_id' => $duplicate->id,
                            'reference_number' => $duplicate->reference_number,
                            'transaction_date' => optional($duplicate->transaction_date)->toDateTimeString(),
                        ] : null,
                    ],
                ]);

                $importedCount++;
            }

            $importBatch->update(['imported_rows' => $importedCount]);
        });

        return redirect()->route('finance.reconciliations.show', $reconciliation)->with('status', 'Statement berhasil diimport: ' . $importedCount . ' baris.');
    }

    public function complete(CompleteBankReconciliationRequest $request, BankReconciliation $reconciliation): RedirectResponse
    {
        if ($reconciliation->status === BankReconciliation::STATUS_COMPLETED) {
            return redirect()->route('finance.reconciliations.show', $reconciliation)->with('status', 'Reconciliation sudah completed.');
        }

        $candidatePayments = $this->candidatePayments($reconciliation)->get()->keyBy('id');
        $candidateFinanceTransactions = $this->candidateFinanceTransactions($reconciliation)->get()->keyBy('id');
        $paymentIds = collect($request->input('payment_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
        $statementLineIds = collect($request->input('statement_line_ids', []))
            ->map(function ($id) { return (int) $id; })
            ->filter()
            ->unique()
            ->values();
        $statementLines = BankStatementLine::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', (int) CompanyContext::currentId())
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->whereIn('id', $statementLineIds->all())
            ->get()
            ->keyBy('id');
        $statementMatches = collect($request->input('statement_matches', []));
        $lineTargets = [];

        foreach ($paymentIds as $paymentId) {
            if (!$candidatePayments->has($paymentId)) {
                abort(422, 'Ada payment yang tidak valid untuk account atau periode reconciliation ini.');
            }
        }

        foreach ($statementLineIds as $lineId) {
            if (!$statementLines->has($lineId)) {
                abort(422, 'Ada statement line yang tidak valid untuk sesi reconciliation ini.');
            }

            $line = $statementLines->get($lineId);
            $matchPayload = $statementMatches->get((string) $lineId, $statementMatches->get($lineId, []));
            $targetType = (string) ($matchPayload['target_type'] ?? '');
            $targetId = (int) ($matchPayload['target_id'] ?? 0);

            if ($targetType === '' && $line->suggested_reconcilable_id) {
                if ($line->suggested_reconcilable_type === Payment::class) {
                    $targetType = 'payment';
                    $targetId = (int) $line->suggested_reconcilable_id;
                } elseif ($line->suggested_reconcilable_type === FinanceTransaction::class) {
                    $targetType = 'finance_transaction';
                    $targetId = (int) $line->suggested_reconcilable_id;
                }
            }

            if ($targetType === 'payment') {
                if (!$candidatePayments->has($targetId)) {
                    abort(422, 'Target payment pada statement line tidak valid untuk account atau periode ini.');
                }

                $paymentIds->push($targetId);
                $lineTargets[$lineId] = [
                    'type' => Payment::class,
                    'id' => $targetId,
                ];
                continue;
            }

            if ($targetType === 'finance_transaction') {
                if (!$candidateFinanceTransactions->has($targetId)) {
                    abort(422, 'Target finance transaction pada statement line tidak valid untuk account atau periode ini.');
                }

                $lineTargets[$lineId] = [
                    'type' => FinanceTransaction::class,
                    'id' => $targetId,
                ];
                continue;
            }

            abort(422, 'Ada statement line yang dipilih tetapi belum punya target match yang valid.');
        }

        $paymentIds = $paymentIds->unique()->values();

        DB::transaction(function () use (
            $reconciliation,
            $request,
            $paymentIds,
            $candidatePayments,
            $candidateFinanceTransactions,
            $statementLineIds,
            $statementLines,
            $lineTargets
        ) {
            $reconciliation->items()->delete();

            foreach ($paymentIds as $paymentId) {
                /** @var Payment $payment */
                $payment = $candidatePayments->get($paymentId);

                BankReconciliationItem::query()->create([
                    'tenant_id' => TenantContext::currentId(),
                    'company_id' => (int) CompanyContext::currentId(),
                    'bank_reconciliation_id' => $reconciliation->id,
                    'reconcilable_type' => Payment::class,
                    'reconcilable_id' => $payment->id,
                    'cleared_date' => optional($payment->paid_at)->toDateString() ?: $reconciliation->period_end->toDateString(),
                    'cleared_amount' => (float) $payment->amount,
                    'status' => 'cleared',
                    'meta' => [
                        'payment_number' => $payment->payment_number,
                        'payment_method_id' => $payment->payment_method_id,
                        'finance_account_id' => $reconciliation->finance_account_id,
                    ],
                    'created_by' => optional($request->user())->id,
                    'updated_by' => optional($request->user())->id,
                ]);
            }

            Payment::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', (int) CompanyContext::currentId())
                ->whereIn('id', $paymentIds->all())
                ->update([
                    'reconciliation_status' => Payment::RECONCILIATION_RECONCILED,
                    'reconciled_at' => now(),
                    'reconciled_by' => optional($request->user())->id,
                    'updated_by' => optional($request->user())->id,
                ]);

            foreach ($statementLineIds as $lineId) {
                $line = $statementLines->get($lineId);
                $target = $lineTargets[$lineId] ?? null;

                if (!$line || !$target || $target['type'] !== FinanceTransaction::class) {
                    continue;
                }

                $transaction = $candidateFinanceTransactions->get($target['id']);

                BankReconciliationItem::query()->create([
                    'tenant_id' => TenantContext::currentId(),
                    'company_id' => (int) CompanyContext::currentId(),
                    'bank_reconciliation_id' => $reconciliation->id,
                    'reconcilable_type' => FinanceTransaction::class,
                    'reconcilable_id' => $transaction->id,
                    'cleared_date' => optional($transaction->transaction_date)->toDateString() ?: $reconciliation->period_end->toDateString(),
                    'cleared_amount' => (float) $line->amount,
                    'status' => 'cleared',
                    'meta' => [
                        'transaction_number' => $transaction->transaction_number,
                        'finance_account_id' => $transaction->finance_account_id,
                        'matched_from_statement_line_id' => $line->id,
                    ],
                    'created_by' => optional($request->user())->id,
                    'updated_by' => optional($request->user())->id,
                ]);
            }

            foreach ($statementLineIds as $lineId) {
                $line = $statementLines->get($lineId);
                $target = $lineTargets[$lineId] ?? null;

                if (!$line || !$target) {
                    continue;
                }

                $line->update([
                    'match_status' => BankStatementLine::MATCH_STATUS_MATCHED,
                    'matched_reconcilable_type' => $target['type'],
                    'matched_reconcilable_id' => $target['id'],
                    'matched_at' => now(),
                    'matched_by' => optional($request->user())->id,
                ]);
            }

            $reconciliation->update([
                'status' => BankReconciliation::STATUS_COMPLETED,
                'notes' => $request->filled('notes') ? $request->input('notes') : $reconciliation->notes,
                'completed_by' => optional($request->user())->id,
                'completed_at' => now(),
                'updated_by' => optional($request->user())->id,
                'meta' => array_merge($reconciliation->meta ?: [], [
                    'matched_payment_count' => $paymentIds->count(),
                    'matched_payment_total' => round((float) $paymentIds->sum(fn ($id) => (float) optional($candidatePayments->get($id))->amount), 2),
                    'matched_statement_line_count' => count($lineTargets),
                ]),
            ]);
        });

        return redirect()->route('finance.reconciliations.show', $reconciliation)->with('status', 'Reconciliation completed.');
    }

    public function resolveStatementLine(
        ResolveBankStatementLineRequest $request,
        BankReconciliation $reconciliation,
        BankStatementLine $statementLine
    ): RedirectResponse {
        if ((int) $statementLine->bank_reconciliation_id !== (int) $reconciliation->id) {
            abort(404);
        }

        if ($reconciliation->status !== BankReconciliation::STATUS_DRAFT) {
            return redirect()->route('finance.reconciliations.show', $reconciliation)->with('error', 'Statement line hanya bisa di-resolve saat reconciliation masih draft.');
        }

        $status = (string) $request->input('status');

        $statementLine->update([
            'match_status' => $status,
            'resolution_reason' => $request->input('resolution_reason'),
            'resolution_note' => $request->input('resolution_note'),
            'resolved_at' => in_array($status, [BankStatementLine::MATCH_STATUS_EXCEPTION, BankStatementLine::MATCH_STATUS_IGNORED], true) ? now() : null,
            'resolved_by' => in_array($status, [BankStatementLine::MATCH_STATUS_EXCEPTION, BankStatementLine::MATCH_STATUS_IGNORED], true) ? optional($request->user())->id : null,
        ]);

        if ($status === BankStatementLine::MATCH_STATUS_UNMATCHED) {
            $statementLine->update([
                'resolution_reason' => null,
                'resolution_note' => null,
                'resolved_at' => null,
                'resolved_by' => null,
            ]);
        }

        return redirect()->route('finance.reconciliations.show', $reconciliation)->with('status', 'Statement line diperbarui.');
    }

    private function candidatePayments(BankReconciliation $reconciliation)
    {
        $accountMethodIds = PaymentMethod::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', (int) CompanyContext::currentId())
            ->where('finance_account_id', $reconciliation->finance_account_id)
            ->pluck('id');

        return Payment::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', (int) CompanyContext::currentId())
            ->where('status', Payment::STATUS_POSTED)
            ->whereIn('payment_method_id', $accountMethodIds->all())
            ->whereBetween('paid_at', [
                $reconciliation->period_start->copy()->startOfDay(),
                $reconciliation->period_end->copy()->endOfDay(),
            ])
            ->where(function ($query) use ($reconciliation) {
                $query->where('reconciliation_status', '!=', Payment::RECONCILIATION_RECONCILED)
                    ->orWhereExists(function ($exists) use ($reconciliation) {
                        $exists->selectRaw('1')
                            ->from('bank_reconciliation_items')
                            ->where('bank_reconciliation_items.bank_reconciliation_id', $reconciliation->id)
                            ->where('bank_reconciliation_items.reconcilable_type', Payment::class)
                            ->whereColumn('bank_reconciliation_items.reconcilable_id', 'payments.id');
                    });
            })
            ->with(['method', 'allocations.payable', 'reconciler'])
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->orderBy('paid_at')
            ->orderBy('id');
    }

    private function candidateFinanceTransactions(BankReconciliation $reconciliation)
    {
        return FinanceTransaction::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', (int) CompanyContext::currentId())
            ->where('finance_account_id', $reconciliation->finance_account_id)
            ->whereBetween('transaction_date', [
                $reconciliation->period_start->copy()->startOfDay(),
                $reconciliation->period_end->copy()->endOfDay(),
            ])
            ->whereNull('transfer_group_key')
            ->whereNotExists(function ($exists) {
                $exists->selectRaw('1')
                    ->from('bank_reconciliation_items')
                    ->where('bank_reconciliation_items.reconcilable_type', FinanceTransaction::class)
                    ->whereColumn('bank_reconciliation_items.reconcilable_id', 'finance_transactions.id');
            })
            ->with(['category'])
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->orderBy('transaction_date')
            ->orderBy('id');
    }

    private function suggestReconcilableMatch(
        BankReconciliation $reconciliation,
        float $amount,
        string $direction,
        Carbon $transactionDate,
        ?string $referenceNumber,
        ?string $description
    ): ?array {
        $best = $this->bestPaymentSuggestion($reconciliation, $amount, $transactionDate, $referenceNumber, $description);
        $financeTransactionSuggestion = $this->bestFinanceTransactionSuggestion($reconciliation, $amount, $direction, $transactionDate, $referenceNumber, $description);

        if ($best === null) {
            $best = $financeTransactionSuggestion;
        } elseif ($financeTransactionSuggestion !== null && $financeTransactionSuggestion['score'] > $best['score']) {
            $best = $financeTransactionSuggestion;
        }

        return $best && $best['score'] >= 60 ? $best : null;
    }

    private function bestPaymentSuggestion(
        BankReconciliation $reconciliation,
        float $amount,
        Carbon $transactionDate,
        ?string $referenceNumber,
        ?string $description
    ): ?array {
        $payments = $this->candidatePayments($reconciliation)
            ->where('amount', $amount)
            ->get();

        $best = null;

        foreach ($payments as $payment) {
            $score = 50;
            $reason = ['amount_match'];
            $paymentReference = strtolower(trim((string) ($payment->reference_number ?: $payment->external_reference ?: '')));
            $reference = strtolower(trim((string) $referenceNumber));
            $descriptionText = strtolower(trim((string) $description));

            if ($reference !== '' && $paymentReference !== '' && str_contains($reference, $paymentReference)) {
                $score += 35;
                $reason[] = 'reference_match';
            }

            if ($reference !== '' && $paymentReference !== '' && str_contains($paymentReference, $reference)) {
                $score += 35;
                $reason[] = 'reference_match';
            }

            if ($descriptionText !== '' && $paymentReference !== '' && str_contains($descriptionText, $paymentReference)) {
                $score += 20;
                $reason[] = 'description_reference_match';
            }

            $daysGap = abs($transactionDate->diffInDays(optional($payment->paid_at) ?: $transactionDate, false));
            if ($daysGap <= 3) {
                $score += 15;
                $reason[] = 'date_near';
            } elseif ($daysGap <= 7) {
                $score += 8;
                $reason[] = 'date_close';
            }

            if ($best === null || $score > $best['score']) {
                $best = [
                    'type' => Payment::class,
                    'id' => $payment->id,
                    'target' => 'payment',
                    'label' => $payment->payment_number,
                    'score' => $score,
                    'reason' => $reason,
                ];
            }
        }

        return $best;
    }

    private function bestFinanceTransactionSuggestion(
        BankReconciliation $reconciliation,
        float $amount,
        string $direction,
        Carbon $transactionDate,
        ?string $referenceNumber,
        ?string $description
    ): ?array {
        $transactions = $this->candidateFinanceTransactions($reconciliation)
            ->where('amount', $amount)
            ->get();

        $best = null;

        foreach ($transactions as $transaction) {
            $score = 45;
            $reason = ['amount_match'];
            $notesText = strtolower(trim((string) $transaction->notes));
            $transactionNumber = strtolower(trim((string) $transaction->transaction_number));
            $reference = strtolower(trim((string) $referenceNumber));
            $descriptionText = strtolower(trim((string) $description));

            if ($direction === 'in' && $transaction->transaction_type === FinanceTransaction::TYPE_CASH_IN) {
                $score += 15;
                $reason[] = 'direction_match';
            }

            if ($direction === 'out' && in_array($transaction->transaction_type, [FinanceTransaction::TYPE_CASH_OUT, FinanceTransaction::TYPE_EXPENSE], true)) {
                $score += 15;
                $reason[] = 'direction_match';
            }

            if ($reference !== '' && $transactionNumber !== '' && str_contains($reference, $transactionNumber)) {
                $score += 30;
                $reason[] = 'reference_match';
            }

            if ($reference !== '' && $transactionNumber !== '' && str_contains($transactionNumber, $reference)) {
                $score += 30;
                $reason[] = 'reference_match';
            }

            if ($descriptionText !== '' && $notesText !== '' && (str_contains($descriptionText, $notesText) || str_contains($notesText, $descriptionText))) {
                $score += 18;
                $reason[] = 'notes_match';
            }

            $daysGap = abs($transactionDate->diffInDays(optional($transaction->transaction_date) ?: $transactionDate, false));
            if ($daysGap <= 2) {
                $score += 15;
                $reason[] = 'date_near';
            } elseif ($daysGap <= 7) {
                $score += 8;
                $reason[] = 'date_close';
            }

            if ($best === null || $score > $best['score']) {
                $best = [
                    'type' => FinanceTransaction::class,
                    'id' => $transaction->id,
                    'target' => 'finance_transaction',
                    'label' => $transaction->transaction_number,
                    'score' => $score,
                    'reason' => $reason,
                ];
            }
        }

        return $best;
    }

    private function bookClosingBalance(int $accountId, Carbon $periodEnd, ?int $branchId = null): float
    {
        $account = FinanceAccount::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', (int) CompanyContext::currentId())
            ->findOrFail($accountId);

        $baseQuery = FinanceTransaction::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', (int) CompanyContext::currentId())
            ->where('finance_account_id', $accountId)
            ->where('transaction_date', '<=', $periodEnd);

        if ($branchId) {
            $baseQuery->where('branch_id', $branchId);
        } else {
            BranchContext::applyScope($baseQuery);
        }

        $delta = (float) $baseQuery->get()->sum(function (FinanceTransaction $transaction) {
            return $transaction->transaction_type === FinanceTransaction::TYPE_CASH_IN
                ? (float) $transaction->amount
                : (float) (-1 * $transaction->amount);
        });

        return round((float) $account->opening_balance + $delta, 2);
    }

    private function storeStatementFile(UploadedFile $file): string
    {
        return $file->store('finance/reconciliations/statements', 'public');
    }

    private function normalizeNullableString($value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function headerContainsAny($header, array $keys): bool
    {
        foreach ($keys as $key) {
            if ($header->contains($key)) {
                return true;
            }
        }

        return false;
    }

    private function firstPayloadValue($payload, array $keys)
    {
        foreach ($keys as $key) {
            $value = $payload->get($key);

            if ($value !== null && trim((string) $value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function extractStatementDate($payload): Carbon
    {
        $rawDate = $this->firstPayloadValue($payload, [
            'transaction_date',
            'date',
            'posting_date',
            'book_date',
            'value_date',
        ]);

        return Carbon::parse((string) ($rawDate ?: now()));
    }

    private function extractStatementAmount($payload): ?float
    {
        $amount = $this->normalizeAmountValue($this->firstPayloadValue($payload, ['amount', 'transaction_amount']));
        $debit = $this->normalizeAmountValue($this->firstPayloadValue($payload, ['debit', 'debit_amount', 'withdrawal']));
        $credit = $this->normalizeAmountValue($this->firstPayloadValue($payload, ['credit', 'credit_amount', 'deposit']));
        $direction = strtolower(trim((string) $this->firstPayloadValue($payload, ['direction', 'type', 'mutation_type'])));

        if ($credit !== null || $debit !== null) {
            $creditValue = $credit !== null ? abs($credit) : 0.0;
            $debitValue = $debit !== null ? abs($debit) : 0.0;
            $netAmount = $creditValue - $debitValue;

            return round($netAmount, 2);
        }

        if ($amount === null) {
            return null;
        }

        $normalizedAmount = $amount;

        if (in_array($direction, ['debit', 'db', 'out', 'outflow', 'withdrawal'], true)) {
            $normalizedAmount = -1 * abs($amount);
        } elseif (in_array($direction, ['credit', 'cr', 'in', 'inflow', 'deposit'], true)) {
            $normalizedAmount = abs($amount);
        }

        return round($normalizedAmount, 2);
    }

    private function normalizeAmountValue($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9,.\-]/', '', $normalized);

        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === ',') {
            return null;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        }

        return (float) $normalized;
    }

    private function findDuplicateStatementLine(
        BankReconciliation $reconciliation,
        Carbon $transactionDate,
        float $amount,
        string $direction,
        ?string $referenceNumber,
        ?string $externalKey
    ): ?BankStatementLine {
        $query = BankStatementLine::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', (int) CompanyContext::currentId())
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->whereDate('transaction_date', $transactionDate->toDateString())
            ->where('amount', $amount)
            ->where('direction', $direction);

        if ($externalKey) {
            $duplicate = (clone $query)
                ->where('external_key', $externalKey)
                ->latest('id')
                ->first();

            if ($duplicate) {
                return $duplicate;
            }
        }

        if ($referenceNumber) {
            return $query
                ->where('reference_number', $referenceNumber)
                ->latest('id')
                ->first();
        }

        return null;
    }
}
