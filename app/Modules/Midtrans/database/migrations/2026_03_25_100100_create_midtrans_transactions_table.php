<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midtrans_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('order_id', 100)->unique(); // e.g. MDTRANS-1-20260325-A1B2

            // Snap token data
            $table->text('snap_token')->nullable();
            $table->text('snap_redirect_url')->nullable();

            // Transaction details (filled after notification)
            $table->string('transaction_id', 100)->nullable(); // Midtrans transaction_id
            $table->string('payment_type', 50)->nullable();    // gopay, credit_card, bank_transfer, etc.
            $table->decimal('gross_amount', 15, 2);
            $table->string('currency_code', 10)->default('IDR');

            // Status tracking
            $table->string('transaction_status', 30)->default('pending');
            // pending | capture | settlement | deny | cancel | expire | refund | partial_refund | chargeback
            $table->string('fraud_status', 20)->nullable(); // accept | challenge | deny

            // Payable polymorphic (what this payment is for)
            $table->string('payable_type', 100)->nullable();
            $table->unsignedBigInteger('payable_id')->nullable();
            $table->index(['payable_type', 'payable_id']);

            // Link to internal Payment record (created on settlement)
            $table->unsignedBigInteger('payment_id')->nullable()->index();

            // Raw data from Midtrans notification
            $table->json('raw_notification')->nullable();

            // Customer snapshot
            $table->string('customer_name', 150)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->string('customer_phone', 50)->nullable();

            // Description / notes
            $table->string('item_description', 255)->nullable();

            $table->timestamp('settled_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midtrans_transactions');
    }
};
