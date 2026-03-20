<?php

namespace App\Modules\PointOfSale\Services;

use App\Models\User;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\PointOfSale\Models\PosCashSession;
use App\Modules\PointOfSale\Models\PosCashSessionMovement;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosCashSessionService
{
    public function activeSessionFor(User $user): ?PosCashSession
    {
        return PosCashSession::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('cashier_user_id', $user->id)
            ->where('status', PosCashSession::STATUS_ACTIVE)
            ->latest('opened_at')
            ->first();
    }

    public function open(User $user, array $data): PosCashSession
    {
        return DB::transaction(function () use ($user, $data) {
            $active = PosCashSession::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('cashier_user_id', $user->id)
                ->where('status', PosCashSession::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if ($active) {
                throw ValidationException::withMessages([
                    'shift' => 'Kasir ini masih memiliki shift aktif.',
                ]);
            }

            return PosCashSession::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'code' => 'SHIFT-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'cashier_user_id' => $user->id,
                'outlet_id' => $data['outlet_id'] ?? null,
                'status' => PosCashSession::STATUS_ACTIVE,
                'opening_cash_amount' => round((float) $data['opening_cash_amount'], 2),
                'opening_note' => $data['opening_note'] ?? null,
                'opened_at' => now(),
                'meta' => [
                    'opened_via' => 'point-of-sale',
                ],
            ]);
        });
    }

    public function close(PosCashSession $session, User $user, array $data): PosCashSession
    {
        return DB::transaction(function () use ($session, $user, $data) {
            $session = PosCashSession::query()
                ->where('tenant_id', TenantContext::currentId())
                ->with(['payments.method', 'cashMovements', 'sales'])
                ->lockForUpdate()
                ->findOrFail($session->id);

            if (!$session->isActive()) {
                throw ValidationException::withMessages([
                    'shift' => 'Hanya shift aktif yang dapat ditutup.',
                ]);
            }

            if ((int) $session->cashier_user_id !== (int) $user->id && !$user->can('pos.manage-all-shifts')) {
                throw ValidationException::withMessages([
                    'shift' => 'Anda tidak memiliki akses untuk menutup shift ini.',
                ]);
            }

            $expectedCash = $this->expectedCashAmount($session);
            $closingCash = round((float) $data['closing_cash_amount'], 2);

            $session->forceFill([
                'status' => PosCashSession::STATUS_CLOSED,
                'closing_cash_amount' => $closingCash,
                'expected_cash_amount' => $expectedCash,
                'difference_amount' => round($closingCash - $expectedCash, 2),
                'closing_note' => $data['closing_note'] ?? null,
                'closed_at' => now(),
                'closed_by' => $user->id,
            ])->save();

            return $session->load([
                'cashier',
                'closer',
                'payments.method',
                'cashMovements.creator',
                'sales',
            ]);
        });
    }

    public function recordMovement(PosCashSession $session, User $user, array $data): PosCashSessionMovement
    {
        return DB::transaction(function () use ($session, $user, $data) {
            $session = PosCashSession::query()
                ->where('tenant_id', TenantContext::currentId())
                ->lockForUpdate()
                ->findOrFail($session->id);

            if (!$session->isActive()) {
                throw ValidationException::withMessages([
                    'shift' => 'Cash in/out hanya bisa dicatat pada shift aktif.',
                ]);
            }

            if ((int) $session->cashier_user_id !== (int) $user->id && !$user->can('pos.manage-all-shifts')) {
                throw ValidationException::withMessages([
                    'shift' => 'Anda tidak memiliki akses untuk shift ini.',
                ]);
            }

            return $session->cashMovements()->create([
                'tenant_id' => TenantContext::currentId(),
                'movement_type' => $data['movement_type'],
                'amount' => round((float) $data['amount'], 2),
                'notes' => $data['notes'] ?? null,
                'occurred_at' => now(),
                'created_by' => $user->id,
            ]);
        });
    }

    public function expectedCashAmount(PosCashSession $session): float
    {
        $session->loadMissing(['payments.method', 'cashMovements']);

        $cashPayments = (float) $session->payments
            ->filter(function ($payment) {
                return $payment->status === 'posted'
                    && $payment->method
                    && $payment->method->code === PaymentMethod::CODE_CASH;
            })
            ->sum('amount');

        $cashMovements = (float) $session->cashMovements->sum(function (PosCashSessionMovement $movement) {
            return $movement->signedAmount();
        });

        return round((float) $session->opening_cash_amount + $cashPayments + $cashMovements, 2);
    }
}
