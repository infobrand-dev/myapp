<?php

namespace App\Support;

use App\Models\AccountingPeriodLock;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AccountingPeriodLockService
{
    public function ensureDateOpen(string|\DateTimeInterface|null $date, ?int $branchId = null, ?string $label = null): void
    {
        $tenantId = TenantContext::currentId();
        $companyId = CompanyContext::currentId();

        if (!$tenantId || !$companyId || $date === null) {
            return;
        }

        $resolvedDate = Carbon::parse($date)->toDateString();

        $lock = AccountingPeriodLock::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereDate('locked_from', '<=', $resolvedDate)
            ->whereDate('locked_until', '>=', $resolvedDate)
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id');

                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->latest('locked_until')
            ->first();

        if (!$lock) {
            return;
        }

        $context = $label ? " untuk {$label}" : '';

        throw ValidationException::withMessages([
            'period_lock' => "Periode {$lock->locked_from->format('Y-m-d')} s/d {$lock->locked_until->format('Y-m-d')} sudah dikunci{$context}.",
        ]);
    }

    public function create(array $data, ?User $actor = null): AccountingPeriodLock
    {
        return AccountingPeriodLock::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => CompanyContext::currentId(),
            'branch_id' => $data['branch_id'] ?? null,
            'locked_from' => $data['locked_from'],
            'locked_until' => $data['locked_until'],
            'notes' => $data['notes'] ?? null,
            'status' => 'active',
            'created_by' => $actor?->id,
        ]);
    }

    public function release(AccountingPeriodLock $lock, ?User $actor = null): AccountingPeriodLock
    {
        $lock->update([
            'status' => 'released',
            'released_by' => $actor?->id,
            'released_at' => now(),
        ]);

        return $lock->fresh();
    }
}
