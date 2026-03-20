<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->string('status', 30)->default('active');
            $table->foreignId('cashier_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pos_cash_session_id')->nullable()->constrained('pos_cash_sessions')->nullOnDelete();
            $table->unsignedBigInteger('register_id')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('customer_label')->nullable();
            $table->string('currency_code', 10)->default('IDR');
            $table->text('notes')->nullable();
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('item_discount_total', 18, 2)->default(0);
            $table->decimal('order_discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->json('discount_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('held_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_sale_id')->nullable();
            $table->timestamps();

            $table->index(['cashier_user_id', 'status']);
            $table->index(['pos_cash_session_id', 'status']);
            $table->index(['register_id', 'status']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_carts');
    }
};
