<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_period_closings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('closing_scope_key', 80)->default('company');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 30)->default('closed');
            $table->decimal('revenue_total', 18, 2)->default(0);
            $table->decimal('expense_total', 18, 2)->default(0);
            $table->decimal('net_income', 18, 2)->default(0);
            $table->string('retained_earnings_account_code', 50)->default('RETAINED_EARNINGS');
            $table->string('retained_earnings_account_name', 120)->default('Retained Earnings');
            $table->unsignedBigInteger('closing_journal_id')->nullable()->index();
            $table->unsignedBigInteger('period_lock_id')->nullable()->index();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'company_id', 'closing_scope_key', 'period_start', 'period_end'],
                'acct_period_closings_scope_unique'
            );
            $table->index(['tenant_id', 'company_id', 'status', 'period_end'], 'acct_period_closings_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_period_closings');
    }
};
