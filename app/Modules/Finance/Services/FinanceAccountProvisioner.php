<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\FinanceAccount;
use Illuminate\Support\Str;

class FinanceAccountProvisioner
{
    public function ensureDefaults(int $tenantId, int $companyId, ?int $userId = null): void
    {
        if (FinanceAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->exists()) {
            return;
        }

        $defaults = [
            [
                'name' => 'Cash on Hand',
                'slug' => 'cash-on-hand',
                'account_type' => FinanceAccount::TYPE_CASH,
                'account_number' => null,
                'is_active' => true,
                'is_default' => true,
                'notes' => 'Akun kas utama untuk transaksi tunai operasional.',
            ],
            [
                'name' => 'Main Bank',
                'slug' => 'main-bank',
                'account_type' => FinanceAccount::TYPE_BANK,
                'account_number' => null,
                'is_active' => true,
                'is_default' => false,
                'notes' => 'Akun bank utama untuk transaksi rekening operasional.',
            ],
        ];

        foreach ($defaults as $row) {
            FinanceAccount::query()->create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'name' => $row['name'],
                'slug' => $this->uniqueSlug($tenantId, $companyId, $row['slug']),
                'account_type' => $row['account_type'],
                'account_number' => $row['account_number'],
                'is_active' => $row['is_active'],
                'is_default' => $row['is_default'],
                'notes' => $row['notes'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    private function uniqueSlug(int $tenantId, int $companyId, string $base): string
    {
        $candidate = Str::slug($base) ?: 'finance-account';
        $suffix = 2;

        while (FinanceAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}
