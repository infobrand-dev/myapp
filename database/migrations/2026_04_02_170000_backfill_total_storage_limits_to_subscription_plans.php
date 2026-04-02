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

        $storageByPlanCode = [
            'free' => 536870912,
            'starter' => 1073741824,
            'starter-v2' => 1073741824,
            'starter-6m-v2' => 1073741824,
            'starter-yearly-v2' => 1073741824,
            'growth' => 5368709120,
            'growth-v2' => 5368709120,
            'growth-6m-v2' => 5368709120,
            'growth-yearly-v2' => 5368709120,
            'scale' => 21474836480,
            'scale-v2' => 21474836480,
            'scale-6m-v2' => 21474836480,
            'scale-yearly-v2' => 21474836480,
            'internal-unlimited' => -1,
        ];

        DB::table('subscription_plans')
            ->select(['id', 'code', 'limits'])
            ->orderBy('id')
            ->each(function ($plan) use ($storageByPlanCode): void {
                $storageLimit = $storageByPlanCode[$plan->code] ?? null;
                if ($storageLimit === null) {
                    return;
                }

                $limits = json_decode((string) ($plan->limits ?? '{}'), true);
                $limits = is_array($limits) ? $limits : [];

                if (array_key_exists(PlanLimit::TOTAL_STORAGE_BYTES, $limits)) {
                    return;
                }

                $limits[PlanLimit::TOTAL_STORAGE_BYTES] = $storageLimit;

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'limits' => json_encode($limits),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        DB::table('subscription_plans')
            ->select(['id', 'limits'])
            ->orderBy('id')
            ->each(function ($plan): void {
                $limits = json_decode((string) ($plan->limits ?? '{}'), true);
                $limits = is_array($limits) ? $limits : [];

                if (!array_key_exists(PlanLimit::TOTAL_STORAGE_BYTES, $limits)) {
                    return;
                }

                unset($limits[PlanLimit::TOTAL_STORAGE_BYTES]);

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'limits' => json_encode($limits),
                        'updated_at' => now(),
                    ]);
            });
    }
};
