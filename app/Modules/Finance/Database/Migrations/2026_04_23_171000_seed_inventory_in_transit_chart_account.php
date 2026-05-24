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

            DB::statement(
                'insert into chart_of_accounts
                (tenant_id, company_id, parent_id, code, name, account_type, normal_balance, report_section, is_postable, is_active, sort_order, created_at, updated_at)
                values (?, ?, ?, ?, ?, ?, ?, ?, true, true, ?, ?, ?)',
                [
                    $scope->tenant_id,
                    $scope->company_id,
                    $assetParentId,
                    'INVENTORY_IN_TRANSIT',
                    'Inventory In Transit',
                    ChartOfAccount::TYPE_ASSET,
                    ChartOfAccount::NORMAL_DEBIT,
                    ChartOfAccount::SECTION_BALANCE_SHEET,
                    106,
                    now(),
                    now(),
                ]
            );
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
