<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('internal_name');
            $table->string('public_label')->nullable();
            $table->string('code', 100)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('discount_type', 50);
            $table->string('application_scope', 30)->default('item');
            $table->string('currency_code', 3)->default('IDR');
            $table->unsignedInteger('priority')->default(100);
            $table->unsignedInteger('sequence')->default(100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_voucher_required')->default(false);
            $table->boolean('is_manual_only')->default(false);
            $table->boolean('is_override_allowed')->default(false);
            $table->string('stack_mode', 30)->default('stackable');
            $table->string('combination_mode', 30)->default('combinable');
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->decimal('max_discount_amount', 18, 2)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('schedule_json')->nullable();
            $table->json('rule_payload')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_archived']);
            $table->index(['priority', 'sequence']);
            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('discount_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->string('target_type', 50);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_code', 100)->nullable();
            $table->string('operator', 20)->default('include');
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['discount_id', 'target_type']);
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('discount_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->string('condition_type', 50);
            $table->string('operator', 20)->default('>=');
            $table->string('value_type', 20)->default('string');
            $table->string('value')->nullable();
            $table->decimal('secondary_value', 18, 4)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['discount_id', 'condition_type']);
        });

        Schema::create('discount_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->string('code', 100)->unique();
            $table->string('description')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['discount_id', 'is_active']);
        });

        Schema::create('discount_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('discount_vouchers')->nullOnDelete();
            $table->string('usage_reference_type', 100)->nullable();
            $table->string('usage_reference_id', 100)->nullable();
            $table->string('customer_reference_type', 100)->nullable();
            $table->string('customer_reference_id', 100)->nullable();
            $table->string('outlet_reference', 100)->nullable();
            $table->string('sales_channel', 50)->nullable();
            $table->string('usage_status', 30)->default('applied');
            $table->string('currency_code', 3)->default('IDR');
            $table->decimal('subtotal_before', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('grand_total_after', 18, 2)->default(0);
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->json('snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['discount_id', 'usage_status']);
            $table->index(['voucher_id', 'usage_status']);
            $table->index(['usage_reference_type', 'usage_reference_id'], 'discount_usage_reference_index');
            $table->index(['customer_reference_type', 'customer_reference_id'], 'discount_usage_customer_index');
        });

        Schema::create('discount_usage_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_usage_id')->constrained('discount_usages')->cascadeOnDelete();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('discount_vouchers')->nullOnDelete();
            $table->string('line_key', 100)->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('subtotal_before', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total_after', 18, 2)->default(0);
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->index(['discount_usage_id', 'discount_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_usage_lines');
        Schema::dropIfExists('discount_usages');
        Schema::dropIfExists('discount_vouchers');
        Schema::dropIfExists('discount_conditions');
        Schema::dropIfExists('discount_targets');
        Schema::dropIfExists('discounts');
    }
};
