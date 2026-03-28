<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_payments')) {
            return;
        }

        Schema::create('platform_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('platform_invoice_id')->constrained('platform_invoices')->cascadeOnDelete();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->string('status', 30)->default('paid');
            $table->string('payment_channel', 50)->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'paid_at'], 'platform_payments_tenant_status_paid_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_payments');
    }
};
