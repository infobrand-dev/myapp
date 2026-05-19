<?php

namespace App\Modules\Finance\Actions;

use App\Models\AccountingJournal;
use App\Models\AccountingPeriodClosing;
use App\Models\User;
use App\Support\AccountingPeriodLockService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\SensitiveActionApprovalService;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReopenAccountingPeriodClosingAction
{
    private $reverseJournalAction;
    private $periodLockService;
    private $approvalService;

    public function __construct(
        ReverseAccountingJournalAction $reverseJournalAction,
        AccountingPeriodLockService $periodLockService,
        SensitiveActionApprovalService $approvalService
    ) {
        $this->reverseJournalAction = $reverseJournalAction;
        $this->periodLockService = $periodLockService;
        $this->approvalService = $approvalService;
    }

    public function execute(AccountingPeriodClosing $closing, array $data, ?User $actor = null): AccountingPeriodClosing
    {
        return DB::transaction(function () use ($closing, $data, $actor) {
            $closing = AccountingPeriodClosing::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with(['closingJournal.reversals', 'periodLock'])
                ->when(BranchContext::currentId(), function ($query) {
                    return $query->where(function ($inner) {
                        $inner->whereNull('branch_id')->orWhere('branch_id', BranchContext::currentId());
                    });
                })
                ->lockForUpdate()
                ->findOrFail($closing->id);

            if (!$closing->canBeReopened()) {
                throw ValidationException::withMessages([
                    'closing' => 'Period closing ini tidak bisa di-reopen.',
                ]);
            }

            $entryDate = Carbon::parse($data['entry_date']);
            $reason = trim((string) ($data['reason'] ?? ''));
            $this->approvalService->ensureApprovedOrCreatePending(
                'finance',
                'reopen_period_closing',
                $closing,
                [
                    'amount' => round(abs((float) $closing->net_income), 2),
                    'closing_scope_key' => $closing->closing_scope_key,
                    '_action_date' => $entryDate->toDateTimeString(),
                    '_maker_ids' => array_values(array_unique(array_filter([
                        (int) $closing->closed_by,
                    ]))),
                ],
                $actor,
                $reason !== '' ? $reason : 'Reopen period closing'
            );

            if ($closing->periodLock && $closing->periodLock->status === 'active') {
                $this->periodLockService->release($closing->periodLock, $actor);
            }

            $journal = $closing->closingJournal;
            if (!$journal instanceof AccountingJournal) {
                throw ValidationException::withMessages([
                    'closing' => 'Closing journal tidak ditemukan.',
                ]);
            }

            $reversal = $this->reverseJournalAction->execute($journal, [
                'entry_date' => $entryDate->toDateTimeString(),
                'reason' => $reason,
            ], $actor);

            $meta = is_array($closing->meta) ? $closing->meta : [];
            $meta['reopened_reason'] = $reason;
            $meta['reopened_entry_date'] = $entryDate->toDateTimeString();
            $meta['reopened_by_journal_id'] = $reversal->id;

            $closing->fill([
                'status' => 'reopened',
                'reopening_journal_id' => $reversal->id,
                'reopened_by' => $actor ? $actor->id : auth()->id(),
                'reopened_at' => now(),
                'meta' => $meta,
            ]);
            $closing->save();

            return $closing->fresh(['closingJournal', 'reopeningJournal', 'periodLock', 'closer', 'reopener']);
        });
    }
}
