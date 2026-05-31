<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('currency_code', 10)->default('IDR');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'currency_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_accounts');
    }
};
