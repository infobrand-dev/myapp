<?php

use App\Support\PlanFeature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('subscription_plans')) {
            return;
        }

        $enabledCodes = ['starter', 'growth', 'scale', 'internal-unlimited'];

        DB::table('subscription_plans')
            ->select(['id', 'code', 'features'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan) use ($enabledCodes): void {
                $features = json_decode((string) ($plan->features ?? '{}'), true);
                if (!is_array($features)) {
                    $features = [];
                }

                $features[PlanFeature::CRM] = in_array((string) $plan->code, $enabledCodes, true);

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'features' => json_encode($features),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('subscription_plans')) {
            return;
        }

        DB::table('subscription_plans')
            ->select(['id', 'features'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan): void {
                $features = json_decode((string) ($plan->features ?? '{}'), true);
                if (!is_array($features)) {
                    return;
                }

                unset($features[PlanFeature::CRM]);

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'features' => json_encode($features),
                        'updated_at' => now(),
                    ]);
            });
    }
};
