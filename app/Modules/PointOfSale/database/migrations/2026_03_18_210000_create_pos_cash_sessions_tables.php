<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('cashier_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('outlet_id')->nullable();
            $table->string('status', 20)->default('active');
            $table->decimal('opening_cash_amount', 18, 2)->default(0);
            $table->text('opening_note')->nullable();
            $table->timestamp('opened_at');
            $table->decimal('closing_cash_amount', 18, 2)->nullable();
            $table->decimal('expected_cash_amount', 18, 2)->nullable();
            $table->decimal('difference_amount', 18, 2)->nullable();
            $table->text('closing_note')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['cashier_user_id', 'status']);
            $table->index(['outlet_id', 'status']);
        });

        Schema::create('pos_cash_session_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_session_id')->constrained('pos_cash_sessions')->cascadeOnDelete();
            $table->string('movement_type', 20);
            $table->decimal('amount', 18, 2);
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['cash_session_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_cash_session_movements');
        Schema::dropIfExists('pos_cash_sessions');
    }
};
