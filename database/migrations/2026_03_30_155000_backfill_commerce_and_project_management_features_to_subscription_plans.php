<?php

use App\Support\PlanFeature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subscription_plans')
            ->select(['id', 'code', 'features'])
            ->orderBy('id')
            ->chunkById(100, function ($plans): void {
                foreach ($plans as $plan) {
                    $features = json_decode($plan->features ?? '{}', true);

                    if (! is_array($features)) {
                        $features = [];
                    }

                    $changed = false;

                    if (! array_key_exists(PlanFeature::COMMERCE, $features)) {
                        $features[PlanFeature::COMMERCE] = $plan->code === 'internal-unlimited';
                        $changed = true;
                    }

                    if (! array_key_exists(PlanFeature::PROJECT_MANAGEMENT, $features)) {
                        $features[PlanFeature::PROJECT_MANAGEMENT] = $plan->code === 'internal-unlimited';
                        $changed = true;
                    }

                    if ($changed) {
                        DB::table('subscription_plans')
                            ->where('id', $plan->id)
                            ->update([
                                'features' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                'updated_at' => now(),
                            ]);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('subscription_plans')
            ->select(['id', 'features'])
            ->orderBy('id')
            ->chunkById(100, function ($plans): void {
                foreach ($plans as $plan) {
                    $features = json_decode($plan->features ?? '{}', true);

                    if (! is_array($features)) {
                        continue;
                    }

                    unset($features[PlanFeature::COMMERCE], $features[PlanFeature::PROJECT_MANAGEMENT]);

                    DB::table('subscription_plans')
                        ->where('id', $plan->id)
                        ->update([
                            'features' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'updated_at' => now(),
                        ]);
                }
            });
    }
};
