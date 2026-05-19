<?php

use App\Modules\Finance\Models\ChartOfAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chart_of_accounts')) {
            return;
        }

        $scopes = DB::table('chart_of_accounts')
            ->select('tenant_id', 'company_id')
            ->groupBy('tenant_id', 'company_id')
            ->get();

        foreach ($scopes as $scope) {
            $assetParentId = DB::table('chart_of_accounts')
                ->where('tenant_id', $scope->tenant_id)
                ->where('company_id', $scope->company_id)
                ->where('code', 'ASSET')
                ->value('id');
            $liabilityParentId = DB::table('chart_of_accounts')
                ->where('tenant_id', $scope->tenant_id)
                ->where('company_id', $scope->company_id)
                ->where('code', 'LIAB')
                ->value('id');

            $this->insertAccountIfMissing($scope, 'PPH_RECEIVABLE', 'PPh Withholding Receivable', ChartOfAccount::TYPE_ASSET, ChartOfAccount::NORMAL_DEBIT, $assetParentId, 107);
            $this->insertAccountIfMissing($scope, 'PPH_PAYABLE', 'PPh Withholding Payable', ChartOfAccount::TYPE_LIABILITY, ChartOfAccount::NORMAL_CREDIT, $liabilityParentId, 203);
            $this->insertAccountIfMissing($scope, 'TAX_ADJUSTMENT', 'Tax Adjustment Clearing', ChartOfAccount::TYPE_ASSET, ChartOfAccount::NORMAL_DEBIT, $assetParentId, 108);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('chart_of_accounts')) {
            return;
        }

        DB::table('chart_of_accounts')
            ->whereIn('code', ['PPH_RECEIVABLE', 'PPH_PAYABLE', 'TAX_ADJUSTMENT'])
            ->delete();
    }

    private function insertAccountIfMissing($scope, string $code, string $name, string $type, string $normalBalance, ?int $parentId, int $sortOrder): void
    {
        $exists = DB::table('chart_of_accounts')
            ->where('tenant_id', $scope->tenant_id)
            ->where('company_id', $scope->company_id)
            ->where('code', $code)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('chart_of_accounts')->insert([
            'tenant_id' => $scope->tenant_id,
            'company_id' => $scope->company_id,
            'parent_id' => $parentId,
            'code' => $code,
            'name' => $name,
            'account_type' => $type,
            'normal_balance' => $normalBalance,
            'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET,
            'is_postable' => true,
            'is_active' => true,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
