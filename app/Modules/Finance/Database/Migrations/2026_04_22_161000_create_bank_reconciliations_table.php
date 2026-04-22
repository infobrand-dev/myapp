<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('finance_account_id')->constrained('finance_accounts')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('statement_ending_balance', 18, 2)->default(0);
            $table->decimal('book_closing_balance', 18, 2)->default(0);
            $table->decimal('difference_amount', 18, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_id', 'finance_account_id', 'period_start', 'period_end'], 'bank_reconciliations_scope_period_idx');
            $table->index(['tenant_id', 'company_id', 'status', 'period_end'], 'bank_reconciliations_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
