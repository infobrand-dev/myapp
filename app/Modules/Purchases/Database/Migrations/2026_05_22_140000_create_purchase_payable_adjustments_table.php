<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payable_adjustment_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('sequence_date', 8);
            $table->string('adjustment_type', 30);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'sequence_date', 'adjustment_type'], 'purchase_payable_adj_seq_unique');
        });

        Schema::create('purchase_payable_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('accounting_journals')->nullOnDelete();
            $table->string('adjustment_number', 50);
            $table->string('adjustment_type', 30);
            $table->dateTime('adjustment_date');
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('status', 30)->default('posted');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'adjustment_number']);
            $table->index(['tenant_id', 'company_id', 'branch_id', 'purchase_id', 'adjustment_type'], 'purchase_payable_adj_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payable_adjustments');
        Schema::dropIfExists('purchase_payable_adjustment_sequences');
    }
};
