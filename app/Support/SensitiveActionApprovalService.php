<?php

namespace App\Support;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestDecision;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SensitiveActionApprovalService
{
    private $approvalMatrixService;

    public function __construct(ApprovalMatrixService $approvalMatrixService)
    {
        $this->approvalMatrixService = $approvalMatrixService;
    }

    public function ensureApprovedOrCreatePending(
        string $module,
        string $action,
        Model $subject,
        array $payload,
        ?User $actor = null,
        ?string $reason = null
    ): void {
        $amount = $this->resolveApprovalAmount($payload);
        $branchId = $subject->branch_id ?? null;
        $matrixRule = $this->approvalMatrixService->applicableRule($module, $action, $branchId, $amount);
        $subjectType = get_class($subject);
        $makerIds = $this->resolveMakerIds($payload);
        $backdateExceeded = $this->approvalMatrixService->exceedsBackdateWindow($matrixRule, data_get($payload, '_action_date'));
        $makerCheckerRequired = $matrixRule
            && (bool) $matrixRule->maker_checker_required
            && $this->isMakerActor($actor, $makerIds);

        if (!$matrixRule && $actor && $actor->can('finance.approve-sensitive-transactions')) {
            return;
        }

        DB::transaction(function () use ($module, $action, $subject, $payload, $actor, $reason, $matrixRule, $amount, $branchId, $subjectType, $makerIds, $makerCheckerRequired, $backdateExceeded): void {
            $payloadHash = hash('sha256', json_encode(Arr::sortRecursive($payload)));
            $requiredApprovals = $matrixRule ? max(1, (int) $matrixRule->required_approvals) : 1;

            $subject->newQuery()
                ->whereKey($subject->getKey())
                ->lockForUpdate()
                ->first();

            $request = ApprovalRequest::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->where('module', $module)
                ->where('action', $action)
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subject->getKey())
                ->where('payload_hash', $payloadHash)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($request && $request->isFullyApproved() && $request->consumed_at === null) {
                if ($this->mustBeAppliedByDifferentActor($request, $actor)) {
                    throw ValidationException::withMessages([
                        'approval' => "Approval request #{$request->id} sudah disetujui, tetapi aksi ini harus dijalankan checker yang berbeda dari maker.",
                    ]);
                }

                $request->update([
                    'status' => ApprovalRequest::STATUS_APPLIED,
                    'consumed_at' => now(),
                ]);

                return;
            }

            if ($request && $request->status === ApprovalRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'approval' => "Aksi sensitif ini menunggu approval. Request #{$request->id} sudah dibuat.",
                ]);
            }

            $subjectLabel = method_exists($subject, 'getAttribute')
                ? (string) ($subject->getAttribute('sale_number')
                    ?? $subject->getAttribute('purchase_number')
                    ?? $subject->getAttribute('payment_number')
                    ?? $subject->getAttribute('transaction_number')
                    ?? class_basename($subject) . ' #' . $subject->getKey())
                : class_basename($subject) . ' #' . $subject->getKey();

            $request = ApprovalRequest::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $branchId,
                'module' => $module,
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subject->getKey(),
                'subject_label' => Str::limit($subjectLabel, 255, ''),
                'status' => ApprovalRequest::STATUS_PENDING,
                'required_approvals' => $requiredApprovals,
                'current_approvals' => 0,
                'payload_hash' => $payloadHash,
                'payload' => array_merge($payload, [
                    '_approval_amount' => $amount,
                    '_approval_rule_id' => $matrixRule ? $matrixRule->id : null,
                    '_approval_required' => $requiredApprovals,
                    '_maker_ids' => $makerIds,
                    '_maker_checker_required' => $makerCheckerRequired,
                    '_backdate_exceeded' => $backdateExceeded,
                ]),
                'reason' => $reason,
                'requested_by' => $actor ? $actor->id : null,
            ]);

            throw ValidationException::withMessages([
                'approval' => "Aksi sensitif memerlukan approval. Request #{$request->id} sudah dibuat.",
            ]);
        });
    }

    public function approve(ApprovalRequest $request, ?User $actor = null, ?string $notes = null): ApprovalRequest
    {
        DB::transaction(function () use ($request, $actor, $notes): void {
            $lockedRequest = ApprovalRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== ApprovalRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'approval' => 'Approval request ini sudah diproses sebelumnya.',
                ]);
            }

            if (!$actor || !$actor->id) {
                throw ValidationException::withMessages([
                    'approval' => 'Approver tidak valid.',
                ]);
            }

            if ((int) $lockedRequest->requested_by === (int) $actor->id) {
                throw ValidationException::withMessages([
                    'approval' => 'Peminta tidak boleh menyetujui request miliknya sendiri.',
                ]);
            }

            $alreadyDecided = ApprovalRequestDecision::query()
                ->where('approval_request_id', $lockedRequest->id)
                ->where('approver_id', $actor->id)
                ->exists();

            if ($alreadyDecided) {
                throw ValidationException::withMessages([
                    'approval' => 'Approver ini sudah pernah memberi keputusan untuk request ini.',
                ]);
            }

            ApprovalRequestDecision::query()->create([
                'approval_request_id' => $lockedRequest->id,
                'tenant_id' => $lockedRequest->tenant_id,
                'company_id' => $lockedRequest->company_id,
                'branch_id' => $lockedRequest->branch_id,
                'approver_id' => $actor->id,
                'decision' => ApprovalRequestDecision::DECISION_APPROVED,
                'notes' => $notes,
                'decided_at' => now(),
            ]);

            $newApprovalCount = (int) $lockedRequest->current_approvals + 1;
            $requiredApprovals = max(1, (int) $lockedRequest->required_approvals);

            $lockedRequest->update([
                'status' => $newApprovalCount >= $requiredApprovals ? ApprovalRequest::STATUS_APPROVED : ApprovalRequest::STATUS_PENDING,
                'approved_by' => $newApprovalCount >= $requiredApprovals ? $actor->id : null,
                'current_approvals' => $newApprovalCount,
                'decision_notes' => $notes,
                'decided_at' => $newApprovalCount >= $requiredApprovals ? now() : null,
            ]);
        });

        return $request->fresh(['requester', 'approver', 'decisions.approver']);
    }

    public function reject(ApprovalRequest $request, ?User $actor = null, ?string $notes = null): ApprovalRequest
    {
        DB::transaction(function () use ($request, $actor, $notes): void {
            $lockedRequest = ApprovalRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== ApprovalRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'approval' => 'Approval request ini sudah diproses sebelumnya.',
                ]);
            }

            if (!$actor || !$actor->id) {
                throw ValidationException::withMessages([
                    'approval' => 'Approver tidak valid.',
                ]);
            }

            if ((int) $lockedRequest->requested_by === (int) $actor->id) {
                throw ValidationException::withMessages([
                    'approval' => 'Peminta tidak boleh menolak request miliknya sendiri.',
                ]);
            }

            $alreadyDecided = ApprovalRequestDecision::query()
                ->where('approval_request_id', $lockedRequest->id)
                ->where('approver_id', $actor->id)
                ->exists();

            if ($alreadyDecided) {
                throw ValidationException::withMessages([
                    'approval' => 'Approver ini sudah pernah memberi keputusan untuk request ini.',
                ]);
            }

            ApprovalRequestDecision::query()->create([
                'approval_request_id' => $lockedRequest->id,
                'tenant_id' => $lockedRequest->tenant_id,
                'company_id' => $lockedRequest->company_id,
                'branch_id' => $lockedRequest->branch_id,
                'approver_id' => $actor->id,
                'decision' => ApprovalRequestDecision::DECISION_REJECTED,
                'notes' => $notes,
                'decided_at' => now(),
            ]);

            $lockedRequest->update([
                'status' => ApprovalRequest::STATUS_REJECTED,
                'approved_by' => $actor->id,
                'decision_notes' => $notes,
                'decided_at' => now(),
            ]);
        });

        return $request->fresh(['requester', 'approver', 'decisions.approver']);
    }

    private function resolveApprovalAmount(array $payload): float
    {
        foreach (['amount', 'grand_total', 'total', 'net_amount'] as $key) {
            if (array_key_exists($key, $payload)) {
                return round((float) $payload[$key], 2);
            }
        }

        return 0.0;
    }

    private function resolveMakerIds(array $payload): array
    {
        $makerIds = data_get($payload, '_maker_ids', []);

        if (!is_array($makerIds)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $makerIds))));
    }

    private function isMakerActor(?User $actor, array $makerIds): bool
    {
        if (!$actor || !$actor->id || empty($makerIds)) {
            return false;
        }

        return in_array((int) $actor->id, $makerIds, true);
    }

    private function mustBeAppliedByDifferentActor(ApprovalRequest $request, ?User $actor): bool
    {
        if (!$actor || !$actor->id) {
            return false;
        }

        if (!(bool) data_get($request->payload, '_maker_checker_required', false)) {
            return false;
        }

        return in_array((int) $actor->id, $this->resolveMakerIds($request->payload ?? []), true);
    }
}
