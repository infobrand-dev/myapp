<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_plan_orders')) {
            return;
        }

        Schema::create('platform_plan_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->foreignId('tenant_subscription_id')->nullable()->constrained('tenant_subscriptions')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('status', 30)->default('pending');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->string('billing_period', 50)->nullable();
            $table->string('buyer_email')->nullable();
            $table->string('payment_channel', 50)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'created_at'], 'platform_plan_orders_tenant_status_created_idx');
            $table->index(['status', 'paid_at'], 'platform_plan_orders_status_paid_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_plan_orders');
    }
};
