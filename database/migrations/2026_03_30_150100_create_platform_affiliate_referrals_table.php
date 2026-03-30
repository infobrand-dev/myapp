<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_affiliate_id')->constrained('platform_affiliates')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('platform_plan_order_id')->nullable()->unique()->constrained('platform_plan_orders')->nullOnDelete();
            $table->string('referral_code', 50);
            $table->string('buyer_email')->nullable();
            $table->string('landing_path', 255)->nullable();
            $table->string('status', 30)->default('registered');
            $table->decimal('order_amount', 15, 2)->default(0);
            $table->string('order_currency', 10)->default('IDR');
            $table->decimal('commission_amount', 15, 2)->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['platform_affiliate_id', 'status']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_affiliate_referrals');
    }
};
