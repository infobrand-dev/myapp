<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_listings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('source_tenant_id')->index();
            $table->unsignedBigInteger('source_product_id')->index();
            $table->string('share_code', 32)->unique();
            $table->string('status', 30)->default('active');
            $table->string('commission_type', 20)->default('percentage');
            $table->decimal('commission_rate', 18, 2)->default(0);
            $table->json('landing_page_meta')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'source_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_listings');
    }
};
