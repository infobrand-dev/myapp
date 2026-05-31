<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('wallet_account_id')->index();
            $table->string('source_type', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('entry_type', 50);
            $table->string('state', 30)->default('available');
            $table->string('direction', 10)->default('credit');
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('currency_code', 10)->default('IDR');
            $table->string('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledger_entries');
    }
};
