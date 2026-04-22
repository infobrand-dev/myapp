<?php

namespace App\Modules\Finance\Actions;

use App\Models\AccountingJournal;
use App\Models\User;
use App\Support\AccountingPeriodLockService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReverseAccountingJournalAction
{
    private $periodLockService;

    public function __construct(AccountingPeriodLockService $periodLockService)
    {
        $this->periodLockService = $periodLockService;
    }

    public function execute(AccountingJournal $journal, array $data, ?User $actor = null): AccountingJournal
    {
        return DB::transaction(function () use ($journal, $data, $actor) {
            $journal = AccountingJournal::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with(['lines', 'reversals'])
                ->when(BranchContext::currentId(), function ($query) {
                    return $query->where(function ($inner) {
                        $inner->whereNull('branch_id')->orWhere('branch_id', BranchContext::currentId());
                    });
                })
                ->lockForUpdate()
                ->findOrFail($journal->id);

            if (!$journal->canBeReversed()) {
                throw ValidationException::withMessages([
                    'journal' => 'Journal ini tidak bisa direverse.',
                ]);
            }

            $entryDate = Carbon::parse($data['entry_date']);
            $reason = trim((string) ($data['reason'] ?? ''));

            $this->periodLockService->ensureDateOpen($entryDate, $journal->branch_id, 'reverse journal');

            $reversal = new AccountingJournal();
            $reversal->fill([
                'tenant_id' => $journal->tenant_id,
                'company_id' => $journal->company_id,
                'branch_id' => $journal->branch_id,
                'entry_type' => 'reversal',
                'source_type' => AccountingJournal::class,
                'source_id' => $journal->id,
                'journal_number' => 'JRNL-REV-' . $entryDate->format('YmdHis'),
                'entry_date' => $entryDate,
                'status' => 'posted',
                'description' => 'Reversal journal ' . ($journal->journal_number ?: ('#' . $journal->id)),
                'reversal_of_journal_id' => $journal->id,
                'meta' => [
                    'reversal_reason' => $reason,
                    'reversal_of_journal_number' => $journal->journal_number,
                    'reversal_of_entry_type' => $journal->entry_type,
                ],
                'created_by' => $actor ? $actor->id : auth()->id(),
                'updated_by' => $actor ? $actor->id : auth()->id(),
            ]);
            $reversal->save();

            foreach ($journal->lines as $index => $line) {
                $lineMeta = is_array($line->meta) ? $line->meta : [];
                $lineMeta['reversal_of_line_id'] = $line->id;

                $reversal->lines()->create([
                    'tenant_id' => $journal->tenant_id,
                    'company_id' => $journal->company_id,
                    'branch_id' => $journal->branch_id,
                    'line_no' => $index + 1,
                    'account_code' => $line->account_code,
                    'account_name' => $line->account_name,
                    'debit' => (float) $line->credit,
                    'credit' => (float) $line->debit,
                    'meta' => $lineMeta,
                ]);
            }

            $originalMeta = is_array($journal->meta) ? $journal->meta : [];
            $originalMeta['reversed_at'] = $entryDate->toDateTimeString();
            $originalMeta['reversed_by_journal_id'] = $reversal->id;
            $originalMeta['reversal_reason'] = $reason;

            $journal->fill([
                'meta' => $originalMeta,
                'updated_by' => $actor ? $actor->id : auth()->id(),
            ]);
            $journal->save();

            return $reversal->load(['lines', 'reversalOf']);
        });
    }
}
