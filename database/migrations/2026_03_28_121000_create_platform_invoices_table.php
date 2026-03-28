<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_invoices')) {
            return;
        }

        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('platform_plan_order_id')->nullable()->constrained('platform_plan_orders')->nullOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('status', 30)->default('issued');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'created_at'], 'platform_invoices_tenant_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_invoices');
    }
};
