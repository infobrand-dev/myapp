<?php

use App\Support\PlanFeature;
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

        $plans = DB::table('subscription_plans')->get(['id', 'code', 'name', 'features', 'limits', 'meta']);

        foreach ($plans as $plan) {
            $features = $this->decodeJson($plan->features);
            $limits = $this->decodeJson($plan->limits);
            $meta = $this->decodeJson($plan->meta);

            [$managedEnabled, $customSmtpEnabled, $managedQuota] = $this->entitlementForPlan(
                (string) $plan->code,
                (string) $plan->name,
                $meta
            );

            if ($managedEnabled === null) {
                continue;
            }

            $features[PlanFeature::TRANSACTIONAL_EMAIL_MANAGED] = $managedEnabled;
            $features[PlanFeature::TRANSACTIONAL_EMAIL_CUSTOM_SMTP] = $customSmtpEnabled;
            $limits[PlanLimit::TRANSACTIONAL_EMAILS_MONTHLY] = $managedQuota;

            DB::table('subscription_plans')
                ->where('id', $plan->id)
                ->update([
                    'features' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'limits' => json_encode($limits, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $plans = DB::table('subscription_plans')->get(['id', 'code', 'meta', 'features', 'limits']);

        foreach ($plans as $plan) {
            $meta = $this->decodeJson($plan->meta);

            if (!$this->isAccountingPlan((string) $plan->code, $meta) && $plan->code !== 'internal-unlimited') {
                continue;
            }

            $features = $this->decodeJson($plan->features);
            $limits = $this->decodeJson($plan->limits);

            unset($features[PlanFeature::TRANSACTIONAL_EMAIL_MANAGED], $features[PlanFeature::TRANSACTIONAL_EMAIL_CUSTOM_SMTP]);
            unset($limits[PlanLimit::TRANSACTIONAL_EMAILS_MONTHLY]);

            DB::table('subscription_plans')
                ->where('id', $plan->id)
                ->update([
                    'features' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'limits' => json_encode($limits, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
        }
    }

    private function entitlementForPlan(string $code, string $name, array $meta): array
    {
        if ($code === 'internal-unlimited') {
            return [true, true, -1];
        }

        if (!$this->isAccountingPlan($code, $meta)) {
            return [null, null, null];
        }

        $tier = strtolower($name);

        return match (true) {
            str_contains($code, 'scale') || $tier === 'scale' => [true, true, 5000],
            str_contains($code, 'growth') || $tier === 'growth' => [true, true, 1000],
            default => [true, false, 200],
        };
    }

    private function isAccountingPlan(string $code, array $meta): bool
    {
        return str_starts_with($code, 'accounting_')
            || in_array(($meta['product_line'] ?? null), ['accounting', 'commerce'], true);
    }

    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
};
