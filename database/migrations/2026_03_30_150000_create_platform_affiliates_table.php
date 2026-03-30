<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 50)->nullable();
            $table->string('referral_code', 50)->unique();
            $table->string('status', 30)->default('active');
            $table->string('commission_type', 30)->default('percentage');
            $table->decimal('commission_rate', 12, 2)->default(10);
            $table->text('notes')->nullable();
            $table->json('payout_meta')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('welcome_emailed_at')->nullable();
            $table->timestamp('last_sale_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_affiliates');
    }
};
