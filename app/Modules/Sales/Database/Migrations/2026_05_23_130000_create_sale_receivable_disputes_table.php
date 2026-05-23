<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_receivable_disputes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('dispute_number')->unique();
            $table->date('dispute_date');
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('status', 40)->default('open');
            $table->string('reason', 160);
            $table->string('outcome_type', 40)->nullable();
            $table->text('notes')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_id', 'status'], 'sale_receivable_disputes_scope_status_idx');
            $table->index(['sale_id', 'status'], 'sale_receivable_disputes_sale_status_idx');
        });

        Schema::create('sale_receivable_dispute_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->string('sequence_date', 8);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'sequence_date'], 'sale_receivable_dispute_sequences_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_receivable_dispute_sequences');
        Schema::dropIfExists('sale_receivable_disputes');
    }
};
