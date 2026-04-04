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

        DB::table('subscription_plans')
            ->select(['id', 'code', 'features'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan): void {
                $features = json_decode((string) ($plan->features ?? '{}'), true);
                $features = is_array($features) ? $features : [];

                $legacyPos = (bool) ($features['pos'] ?? false);
                unset($features['pos']);

                if (($plan->code ?? null) === 'internal-unlimited') {
                    $features[PlanFeature::POINT_OF_SALE] = true;
                } elseif (str_starts_with((string) ($plan->code ?? ''), 'accounting_')) {
                    $features[PlanFeature::POINT_OF_SALE] = false;
                } elseif (!array_key_exists(PlanFeature::POINT_OF_SALE, $features) && $legacyPos) {
                    $features[PlanFeature::POINT_OF_SALE] = true;
                }

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'features' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
            ->select(['id', 'code', 'features'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan): void {
                $features = json_decode((string) ($plan->features ?? '{}'), true);
                $features = is_array($features) ? $features : [];

                $pointOfSale = (bool) ($features[PlanFeature::POINT_OF_SALE] ?? false);

                if (($plan->code ?? null) === 'internal-unlimited' || str_starts_with((string) ($plan->code ?? ''), 'accounting_')) {
                    $features['pos'] = $pointOfSale;
                }

                unset($features[PlanFeature::POINT_OF_SALE]);

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'features' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
            });
    }
};
