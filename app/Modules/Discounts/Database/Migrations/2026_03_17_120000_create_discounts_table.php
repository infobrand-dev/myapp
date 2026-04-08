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
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('internal_name');
            $table->string('public_label')->nullable();
            $table->string('code', 100)->nullable();
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

            $table->unique(['tenant_id', 'code']);
            $table->index(['is_active', 'is_archived']);
            $table->index(['priority', 'sequence']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
