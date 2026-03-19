<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('transaction_number', 50);
            $table->string('transaction_type', 20);
            $table->dateTime('transaction_date');
            $table->decimal('amount', 18, 2);
            $table->foreignId('finance_category_id')->constrained('finance_categories')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('outlet_id')->nullable();
            $table->foreignId('pos_cash_session_id')->nullable()->constrained('pos_cash_sessions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'transaction_number']);
            $table->index(['transaction_type', 'transaction_date']);
            $table->index(['finance_category_id', 'transaction_date']);
            $table->index(['created_by', 'transaction_date']);
            $table->index(['outlet_id', 'transaction_date']);
            $table->index(['pos_cash_session_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};
