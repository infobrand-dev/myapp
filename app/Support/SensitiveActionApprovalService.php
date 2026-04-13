<?php

namespace App\Support;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SensitiveActionApprovalService
{
    public function ensureApprovedOrCreatePending(
        string $module,
        string $action,
        Model $subject,
        array $payload,
        ?User $actor = null,
        ?string $reason = null
    ): void {
        if ($actor && $actor->can('finance.approve-sensitive-transactions')) {
            return;
        }

        DB::transaction(function () use ($module, $action, $subject, $payload, $actor, $reason): void {
            $payloadHash = hash('sha256', json_encode(Arr::sortRecursive($payload)));

            $subject->newQuery()
                ->whereKey($subject->getKey())
                ->lockForUpdate()
                ->first();

            $request = ApprovalRequest::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->where('module', $module)
                ->where('action', $action)
                ->where('subject_type', $subject::class)
                ->where('subject_id', $subject->getKey())
                ->where('payload_hash', $payloadHash)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($request && $request->status === ApprovalRequest::STATUS_APPROVED && $request->consumed_at === null) {
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
                'branch_id' => $subject->branch_id ?? null,
                'module' => $module,
                'action' => $action,
                'subject_type' => $subject::class,
                'subject_id' => $subject->getKey(),
                'subject_label' => Str::limit($subjectLabel, 255, ''),
                'status' => ApprovalRequest::STATUS_PENDING,
                'payload_hash' => $payloadHash,
                'payload' => $payload,
                'reason' => $reason,
                'requested_by' => $actor?->id,
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

            $lockedRequest->update([
                'status' => ApprovalRequest::STATUS_APPROVED,
                'approved_by' => $actor?->id,
                'decision_notes' => $notes,
                'decided_at' => now(),
            ]);
        });

        return $request->fresh(['requester', 'approver']);
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

            $lockedRequest->update([
                'status' => ApprovalRequest::STATUS_REJECTED,
                'approved_by' => $actor?->id,
                'decision_notes' => $notes,
                'decided_at' => now(),
            ]);
        });

        return $request->fresh(['requester', 'approver']);
    }
}
