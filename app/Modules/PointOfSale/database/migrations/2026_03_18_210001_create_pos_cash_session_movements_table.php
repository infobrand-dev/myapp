<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_cash_session_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->foreignId('cash_session_id')->constrained('pos_cash_sessions')->cascadeOnDelete();
            $table->string('movement_type', 20);
            $table->decimal('amount', 18, 2);
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_id', 'occurred_at']);
            $table->index(['cash_session_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_cash_session_movements');
    }
};
