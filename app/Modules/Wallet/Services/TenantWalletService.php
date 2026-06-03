<?php

namespace App\Modules\Wallet\Services;

use App\Modules\Wallet\Models\WalletAccount;
use App\Modules\Wallet\Models\WalletLedgerEntry;
use App\Modules\Wallet\Models\WalletPayoutRequest;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TenantWalletService
{
    public function account(?int $tenantId = null): WalletAccount
    {
        return WalletAccount::query()->firstOrCreate([
            'tenant_id' => $tenantId ?: TenantContext::currentId(),
        ], [
            'currency_code' => 'IDR',
            'meta' => null,
        ]);
    }

    public function addEntry(WalletAccount $account, array $payload): WalletLedgerEntry
    {
        return WalletLedgerEntry::query()->create([
            'tenant_id' => (int) $account->tenant_id,
            'wallet_account_id' => (int) $account->id,
            'source_type' => $payload['source_type'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'entry_type' => (string) $payload['entry_type'],
            'state' => (string) ($payload['state'] ?? 'available'),
            'direction' => (string) ($payload['direction'] ?? 'credit'),
            'amount' => round((float) ($payload['amount'] ?? 0), 2),
            'currency_code' => (string) ($payload['currency_code'] ?? 'IDR'),
            'notes' => $payload['notes'] ?? null,
            'meta' => $payload['meta'] ?? null,
            'recorded_at' => $payload['recorded_at'] ?? now(),
        ]);
    }

    /**
     * @return array{pending:float,available:float,locked:float,paid_out:float}
     */
    public function balances(WalletAccount $account): array
    {
        $entries = $account->entries()->get();

        return [
            'pending' => $this->sumByState($entries, 'pending'),
            'available' => $this->sumByState($entries, 'available'),
            'locked' => $this->reservedByState($entries, 'locked'),
            'paid_out' => $this->reservedByState($entries, 'paid_out'),
        ];
    }

    public function spendableBalance(WalletAccount $account): float
    {
        $balances = $this->balances($account);
        $openPayoutRequests = $this->openPayoutRequestAmount($account);

        return round(max(0, $balances['available'] - $balances['locked'] - $openPayoutRequests), 2);
    }

    public function requestPayout(array $payload, ?int $actorId = null): WalletPayoutRequest
    {
        $account = $this->account();
        $spendable = $this->spendableBalance($account);
        $amount = round((float) ($payload['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal payout harus lebih besar dari nol.',
            ]);
        }

        if ($amount > $spendable) {
            throw ValidationException::withMessages([
                'amount' => 'Saldo spendable tidak cukup untuk payout request ini.',
            ]);
        }

        return WalletPayoutRequest::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'wallet_account_id' => (int) $account->id,
            'amount' => $amount,
            'currency_code' => 'IDR',
            'status' => 'requested',
            'destination_snapshot' => [
                'bank_name' => trim((string) ($payload['bank_name'] ?? '')),
                'account_name' => trim((string) ($payload['account_name'] ?? '')),
                'account_number' => trim((string) ($payload['account_number'] ?? '')),
            ],
            'notes' => $payload['notes'] ?? null,
            'meta' => null,
            'requested_by' => $actorId,
            'requested_at' => now(),
        ]);
    }

    public function approve(WalletPayoutRequest $request, ?int $reviewerId = null): WalletPayoutRequest
    {
        if ($request->status === 'paid') {
            return $request->fresh();
        }

        $request->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return $request->fresh();
    }

    public function markPaid(WalletPayoutRequest $request, ?int $reviewerId = null): WalletPayoutRequest
    {
        if ($request->status === 'paid') {
            return $request->fresh();
        }

        $account = $request->account ?: $this->account((int) $request->tenant_id);
        $existingEntry = WalletLedgerEntry::query()
            ->where('tenant_id', (int) $request->tenant_id)
            ->where('source_type', 'wallet_payout_request')
            ->where('source_id', (int) $request->id)
            ->where('entry_type', 'payout')
            ->first();

        if (!$existingEntry) {
            $this->addEntry($account, [
                'source_type' => 'wallet_payout_request',
                'source_id' => (int) $request->id,
                'entry_type' => 'payout',
                'state' => 'paid_out',
                'direction' => 'debit',
                'amount' => (float) $request->amount,
                'notes' => 'Payout marked paid.',
            ]);
        }

        $request->update([
            'status' => 'paid',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => $request->reviewed_at ?: now(),
            'paid_at' => now(),
        ]);

        return $request->fresh();
    }

    public function reject(WalletPayoutRequest $request, ?int $reviewerId = null): WalletPayoutRequest
    {
        $request->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return $request->fresh();
    }

    /**
     * @param  Collection<int, WalletLedgerEntry>  $entries
     */
    private function sumByState(Collection $entries, string $state): float
    {
        return round((float) $entries
            ->where('state', $state)
            ->sum(fn (WalletLedgerEntry $entry) => $entry->direction === 'debit' ? (float) $entry->amount * -1 : (float) $entry->amount), 2);
    }

    /**
     * @param  Collection<int, WalletLedgerEntry>  $entries
     */
    private function reservedByState(Collection $entries, string $state): float
    {
        return round((float) $entries
            ->where('state', $state)
            ->sum(fn (WalletLedgerEntry $entry) => abs((float) $entry->amount)), 2);
    }

    private function openPayoutRequestAmount(WalletAccount $account): float
    {
        return round((float) $account->payoutRequests()
            ->whereIn('status', ['requested', 'approved'])
            ->sum('amount'), 2);
    }
}
