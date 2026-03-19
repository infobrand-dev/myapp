<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_receipt_reprint_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('pos_cash_session_id')->nullable()->constrained('pos_cash_sessions')->nullOnDelete();
            $table->unsignedBigInteger('outlet_id')->nullable();
            $table->unsignedInteger('reprint_sequence');
            $table->text('reason');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['sale_id', 'reprint_sequence'], 'pos_receipt_reprint_logs_sale_sequence_unique');
            $table->index(['requested_by', 'created_at']);
            $table->index(['outlet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_receipt_reprint_logs');
    }
};
