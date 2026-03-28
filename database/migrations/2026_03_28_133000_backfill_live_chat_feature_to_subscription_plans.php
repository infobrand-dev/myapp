<?php

use App\Support\PlanFeature;
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

        $featureMatrix = [
            'free' => false,
            'starter' => true,
            'growth' => true,
            'scale' => true,
            'internal-unlimited' => true,
        ];

        $plans = DB::table('subscription_plans')
            ->select(['id', 'code', 'features'])
            ->whereIn('code', array_keys($featureMatrix))
            ->get();

        foreach ($plans as $plan) {
            $features = json_decode((string) ($plan->features ?? '{}'), true);
            if (!is_array($features)) {
                $features = [];
            }

            $features[PlanFeature::LIVE_CHAT] = $featureMatrix[$plan->code] ?? false;

            DB::table('subscription_plans')
                ->where('id', $plan->id)
                ->update([
                    'features' => json_encode($features),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $plans = DB::table('subscription_plans')
            ->select(['id', 'features'])
            ->get();

        foreach ($plans as $plan) {
            $features = json_decode((string) ($plan->features ?? '{}'), true);
            if (!is_array($features) || !array_key_exists(PlanFeature::LIVE_CHAT, $features)) {
                continue;
            }

            unset($features[PlanFeature::LIVE_CHAT]);

            DB::table('subscription_plans')
                ->where('id', $plan->id)
                ->update([
                    'features' => json_encode($features),
                    'updated_at' => now(),
                ]);
        }
    }
};
