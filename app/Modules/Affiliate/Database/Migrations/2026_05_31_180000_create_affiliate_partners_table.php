<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('referral_code', 32);
            $table->string('commission_type', 20)->default('percentage');
            $table->decimal('commission_rate', 18, 2)->default(0);
            $table->unsignedInteger('cookie_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'referral_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_partners');
    }
};
