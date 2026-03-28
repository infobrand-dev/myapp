<?php

use App\Support\PlanLimit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $limitsByCode = [
            'free' => 0,
            'starter' => 0,
            'growth' => 500,
            'scale' => 2500,
            'internal-unlimited' => -1,
        ];

        foreach ($limitsByCode as $code => $credits) {
            $row = DB::table('subscription_plans')->where('code', $code)->first(['id', 'limits']);
            if (!$row) {
                continue;
            }

            $limits = json_decode((string) ($row->limits ?? '{}'), true);
            if (!is_array($limits)) {
                $limits = [];
            }

            $limits[PlanLimit::AI_CREDITS_MONTHLY] = $credits;

            DB::table('subscription_plans')
                ->where('id', $row->id)
                ->update([
                    'limits' => json_encode($limits),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $plans = DB::table('subscription_plans')->get(['id', 'limits']);
        foreach ($plans as $plan) {
            $limits = json_decode((string) ($plan->limits ?? '{}'), true);
            if (!is_array($limits)) {
                continue;
            }

            unset($limits[PlanLimit::AI_CREDITS_MONTHLY]);

            DB::table('subscription_plans')
                ->where('id', $plan->id)
                ->update([
                    'limits' => json_encode($limits),
                    'updated_at' => now(),
                ]);
        }
    }
};
