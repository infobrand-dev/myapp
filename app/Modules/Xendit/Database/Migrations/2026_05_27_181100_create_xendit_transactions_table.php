<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xendit_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('external_reference')->unique();
            $table->string('invoice_id')->nullable()->index();
            $table->text('invoice_url')->nullable();
            $table->string('status', 50)->default('PENDING')->index();
            $table->string('payment_method', 100)->nullable();
            $table->decimal('gross_amount', 18, 2)->default(0);
            $table->string('currency_code', 10)->default('IDR');
            $table->string('payable_type')->nullable();
            $table->unsignedBigInteger('payable_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->json('raw_notification')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_id']);
            $table->index(['payable_type', 'payable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xendit_transactions');
    }
};
