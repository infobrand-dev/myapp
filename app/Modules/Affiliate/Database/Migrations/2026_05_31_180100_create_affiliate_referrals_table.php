<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('affiliate_partner_id')->nullable()->index();
            $table->unsignedBigInteger('affiliate_listing_id')->nullable()->index();
            $table->unsignedBigInteger('affiliate_tenant_id')->nullable()->index();
            $table->unsignedBigInteger('affiliate_user_id')->nullable()->index();
            $table->unsignedBigInteger('source_product_id')->nullable()->index();
            $table->unsignedBigInteger('sale_id')->index();
            $table->string('referral_code', 32);
            $table->string('landing_url')->nullable();
            $table->string('channel', 50)->nullable();
            $table->string('status', 30)->default('captured');
            $table->string('commission_type', 20)->nullable();
            $table->decimal('commission_amount', 18, 2)->default(0);
            $table->decimal('order_gross', 18, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_referrals');
    }
};
