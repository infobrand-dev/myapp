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

        $defaults = [
            'free' => [
                PlanFeature::COMMERCE => false,
                PlanFeature::PROJECT_MANAGEMENT => false,
            ],
            'starter' => [
                PlanFeature::COMMERCE => false,
                PlanFeature::PROJECT_MANAGEMENT => false,
            ],
            'growth' => [
                PlanFeature::COMMERCE => false,
                PlanFeature::PROJECT_MANAGEMENT => false,
            ],
            'scale' => [
                PlanFeature::COMMERCE => false,
                PlanFeature::PROJECT_MANAGEMENT => false,
            ],
            'starter-v2' => [
                PlanFeature::COMMERCE => false,
                PlanFeature::PROJECT_MANAGEMENT => false,
            ],
            'growth-v2' => [
                PlanFeature::COMMERCE => false,
                PlanFeature::PROJECT_MANAGEMENT => false,
            ],
            'scale-v2' => [
                PlanFeature::COMMERCE => false,
                PlanFeature::PROJECT_MANAGEMENT => false,
            ],
            'internal-unlimited' => [
                PlanFeature::COMMERCE => true,
                PlanFeature::PROJECT_MANAGEMENT => true,
            ],
        ];

        DB::table('subscription_plans')
            ->select(['id', 'code', 'features'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan) use ($defaults): void {
                $featureDefaults = $defaults[$plan->code] ?? null;
                if (!$featureDefaults) {
                    return;
                }

                $features = json_decode((string) ($plan->features ?? '{}'), true);
                $features = is_array($features) ? $features : [];
                $changed = false;

                foreach ($featureDefaults as $key => $value) {
                    if (!array_key_exists($key, $features)) {
                        $features[$key] = $value;
                        $changed = true;
                    }
                }

                if ($changed) {
                    DB::table('subscription_plans')
                        ->where('id', $plan->id)
                        ->update([
                            'features' => json_encode($features),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $keys = [
            PlanFeature::COMMERCE,
            PlanFeature::PROJECT_MANAGEMENT,
        ];

        DB::table('subscription_plans')
            ->select(['id', 'features'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan) use ($keys): void {
                $features = json_decode((string) ($plan->features ?? '{}'), true);
                $features = is_array($features) ? $features : [];

                foreach ($keys as $key) {
                    unset($features[$key]);
                }

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'features' => json_encode($features),
                        'updated_at' => now(),
                    ]);
            });
    }
};
