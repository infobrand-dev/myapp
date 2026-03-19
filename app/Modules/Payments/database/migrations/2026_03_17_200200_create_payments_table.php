<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('payment_number', 50);
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency_code', 10)->default('IDR');
            $table->dateTime('paid_at');
            $table->string('status', 30)->default('posted');
            $table->string('source', 30)->default('backoffice');
            $table->string('channel', 50)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->string('external_reference', 100)->nullable();
            $table->unsignedBigInteger('outlet_id')->nullable();
            $table->foreignId('pos_cash_session_id')->nullable()->constrained('pos_cash_sessions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'payment_number']);
            $table->index(['status', 'paid_at']);
            $table->index(['payment_method_id', 'paid_at']);
            $table->index(['source', 'created_at']);
            $table->index(['received_by', 'paid_at']);
            $table->index(['pos_cash_session_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
