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
            $exists = DB::table('chart_of_accounts')
                ->where('tenant_id', $scope->tenant_id)
                ->where('company_id', $scope->company_id)
                ->where('code', 'INVENTORY_IN_TRANSIT')
                ->exists();

            if ($exists) {
                continue;
            }

            $assetParentId = DB::table('chart_of_accounts')
                ->where('tenant_id', $scope->tenant_id)
                ->where('company_id', $scope->company_id)
                ->where('code', 'ASSET')
                ->value('id');

            DB::table('chart_of_accounts')->insert([
                'tenant_id' => $scope->tenant_id,
                'company_id' => $scope->company_id,
                'parent_id' => $assetParentId,
                'code' => 'INVENTORY_IN_TRANSIT',
                'name' => 'Inventory In Transit',
                'account_type' => ChartOfAccount::TYPE_ASSET,
                'normal_balance' => ChartOfAccount::NORMAL_DEBIT,
                'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET,
                'is_postable' => true,
                'is_active' => true,
                'sort_order' => 106,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('chart_of_accounts')) {
            return;
        }

        DB::table('chart_of_accounts')
            ->where('code', 'INVENTORY_IN_TRANSIT')
            ->delete();
    }
};
