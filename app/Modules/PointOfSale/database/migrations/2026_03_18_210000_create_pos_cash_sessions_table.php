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
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('code', 50);
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

            $table->unique(['tenant_id', 'code']);
            $table->index(['cashier_user_id', 'status']);
            $table->index(['outlet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_cash_sessions');
    }
};
