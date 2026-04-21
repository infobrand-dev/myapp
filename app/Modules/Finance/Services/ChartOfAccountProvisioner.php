<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\ChartOfAccount;

class ChartOfAccountProvisioner
{
    public function ensureDefaults(int $tenantId, int $companyId, ?int $userId = null): void
    {
        if (ChartOfAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->exists()) {
            return;
        }

        $defaults = [
            ['code' => 'ASSET', 'name' => 'Assets', 'account_type' => ChartOfAccount::TYPE_ASSET, 'normal_balance' => ChartOfAccount::NORMAL_DEBIT, 'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET, 'is_postable' => false, 'parent_code' => null, 'sort_order' => 10],
            ['code' => 'LIAB', 'name' => 'Liabilities', 'account_type' => ChartOfAccount::TYPE_LIABILITY, 'normal_balance' => ChartOfAccount::NORMAL_CREDIT, 'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET, 'is_postable' => false, 'parent_code' => null, 'sort_order' => 20],
            ['code' => 'EQ', 'name' => 'Equity', 'account_type' => ChartOfAccount::TYPE_EQUITY, 'normal_balance' => ChartOfAccount::NORMAL_CREDIT, 'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET, 'is_postable' => false, 'parent_code' => null, 'sort_order' => 30],
            ['code' => 'REV', 'name' => 'Revenue', 'account_type' => ChartOfAccount::TYPE_REVENUE, 'normal_balance' => ChartOfAccount::NORMAL_CREDIT, 'report_section' => ChartOfAccount::SECTION_PROFIT_LOSS, 'is_postable' => false, 'parent_code' => null, 'sort_order' => 40],
            ['code' => 'EXP', 'name' => 'Expenses', 'account_type' => ChartOfAccount::TYPE_EXPENSE, 'normal_balance' => ChartOfAccount::NORMAL_DEBIT, 'report_section' => ChartOfAccount::SECTION_PROFIT_LOSS, 'is_postable' => false, 'parent_code' => null, 'sort_order' => 50],

            ['code' => 'CASH', 'name' => 'Cash', 'account_type' => ChartOfAccount::TYPE_ASSET, 'normal_balance' => ChartOfAccount::NORMAL_DEBIT, 'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET, 'is_postable' => true, 'parent_code' => 'ASSET', 'sort_order' => 101],
            ['code' => 'BANK', 'name' => 'Bank', 'account_type' => ChartOfAccount::TYPE_ASSET, 'normal_balance' => ChartOfAccount::NORMAL_DEBIT, 'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET, 'is_postable' => true, 'parent_code' => 'ASSET', 'sort_order' => 102],
            ['code' => 'AR', 'name' => 'Accounts Receivable', 'account_type' => ChartOfAccount::TYPE_ASSET, 'normal_balance' => ChartOfAccount::NORMAL_DEBIT, 'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET, 'is_postable' => true, 'parent_code' => 'ASSET', 'sort_order' => 103],
            ['code' => 'INVENTORY', 'name' => 'Inventory', 'account_type' => ChartOfAccount::TYPE_ASSET, 'normal_balance' => ChartOfAccount::NORMAL_DEBIT, 'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET, 'is_postable' => true, 'parent_code' => 'ASSET', 'sort_order' => 104],

            ['code' => 'AP', 'name' => 'Accounts Payable', 'account_type' => ChartOfAccount::TYPE_LIABILITY, 'normal_balance' => ChartOfAccount::NORMAL_CREDIT, 'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET, 'is_postable' => true, 'parent_code' => 'LIAB', 'sort_order' => 201],
            ['code' => 'SALES_TAX', 'name' => 'Sales Tax Payable', 'account_type' => ChartOfAccount::TYPE_LIABILITY, 'normal_balance' => ChartOfAccount::NORMAL_CREDIT, 'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET, 'is_postable' => true, 'parent_code' => 'LIAB', 'sort_order' => 202],
            ['code' => 'PURCHASE_TAX', 'name' => 'Purchase Tax', 'account_type' => ChartOfAccount::TYPE_ASSET, 'normal_balance' => ChartOfAccount::NORMAL_DEBIT, 'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET, 'is_postable' => true, 'parent_code' => 'ASSET', 'sort_order' => 105],

            ['code' => 'EQUITY', 'name' => 'Owner Equity', 'account_type' => ChartOfAccount::TYPE_EQUITY, 'normal_balance' => ChartOfAccount::NORMAL_CREDIT, 'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET, 'is_postable' => true, 'parent_code' => 'EQ', 'sort_order' => 301],
            ['code' => 'RETAINED_EARNINGS', 'name' => 'Retained Earnings', 'account_type' => ChartOfAccount::TYPE_EQUITY, 'normal_balance' => ChartOfAccount::NORMAL_CREDIT, 'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET, 'is_postable' => true, 'parent_code' => 'EQ', 'sort_order' => 302],

            ['code' => 'SALES', 'name' => 'Sales Revenue', 'account_type' => ChartOfAccount::TYPE_REVENUE, 'normal_balance' => ChartOfAccount::NORMAL_CREDIT, 'report_section' => ChartOfAccount::SECTION_PROFIT_LOSS, 'is_postable' => true, 'parent_code' => 'REV', 'sort_order' => 401],
            ['code' => 'SALES_DISC', 'name' => 'Sales Discount', 'account_type' => ChartOfAccount::TYPE_EXPENSE, 'normal_balance' => ChartOfAccount::NORMAL_DEBIT, 'report_section' => ChartOfAccount::SECTION_PROFIT_LOSS, 'is_postable' => true, 'parent_code' => 'EXP', 'sort_order' => 501],
            ['code' => 'SALES_REFUND', 'name' => 'Sales Refund', 'account_type' => ChartOfAccount::TYPE_EXPENSE, 'normal_balance' => ChartOfAccount::NORMAL_DEBIT, 'report_section' => ChartOfAccount::SECTION_PROFIT_LOSS, 'is_postable' => true, 'parent_code' => 'EXP', 'sort_order' => 502],
            ['code' => 'PURCHASES', 'name' => 'Purchases / Inventory', 'account_type' => ChartOfAccount::TYPE_EXPENSE, 'normal_balance' => ChartOfAccount::NORMAL_DEBIT, 'report_section' => ChartOfAccount::SECTION_PROFIT_LOSS, 'is_postable' => true, 'parent_code' => 'EXP', 'sort_order' => 503],
            ['code' => 'PURCHASE_DISC', 'name' => 'Purchase Discount', 'account_type' => ChartOfAccount::TYPE_REVENUE, 'normal_balance' => ChartOfAccount::NORMAL_CREDIT, 'report_section' => ChartOfAccount::SECTION_PROFIT_LOSS, 'is_postable' => true, 'parent_code' => 'REV', 'sort_order' => 402],
            ['code' => 'LANDED_COST', 'name' => 'Landed Cost', 'account_type' => ChartOfAccount::TYPE_EXPENSE, 'normal_balance' => ChartOfAccount::NORMAL_DEBIT, 'report_section' => ChartOfAccount::SECTION_PROFIT_LOSS, 'is_postable' => true, 'parent_code' => 'EXP', 'sort_order' => 504],
        ];

        $created = [];

        foreach ($defaults as $row) {
            $parentId = null;

            if ($row['parent_code']) {
                $parentId = $created[$row['parent_code']]->id ?? null;
            }

            $created[$row['code']] = ChartOfAccount::query()->create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'parent_id' => $parentId,
                'code' => $row['code'],
                'name' => $row['name'],
                'account_type' => $row['account_type'],
                'normal_balance' => $row['normal_balance'],
                'report_section' => $row['report_section'],
                'is_postable' => $row['is_postable'],
                'is_active' => true,
                'sort_order' => $row['sort_order'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }
}
