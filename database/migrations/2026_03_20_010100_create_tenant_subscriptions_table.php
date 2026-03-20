<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->string('status')->default('active');
            $table->string('billing_provider')->nullable();
            $table->string('billing_reference')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('auto_renews')->default(false);
            $table->json('feature_overrides')->nullable();
            $table->json('limit_overrides')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'starts_at'], 'tenant_subscriptions_tenant_status_start_idx');
            $table->index(['tenant_id', 'ends_at'], 'tenant_subscriptions_tenant_end_idx');
            $table->unique(['tenant_id', 'billing_provider', 'billing_reference'], 'tenant_subscriptions_provider_reference_unique');
        });

        $bootstrapPlanId = DB::table('subscription_plans')
            ->where('code', 'internal-unlimited')
            ->value('id');

        if ($bootstrapPlanId) {
            DB::table('tenant_subscriptions')->insert([
                'tenant_id' => 1,
                'subscription_plan_id' => $bootstrapPlanId,
                'status' => 'active',
                'billing_provider' => null,
                'billing_reference' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'trial_ends_at' => null,
                'auto_renews' => false,
                'feature_overrides' => null,
                'limit_overrides' => null,
                'meta' => json_encode(['bootstrap' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
