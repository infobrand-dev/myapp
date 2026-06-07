<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_journals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('entry_type', 50);
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id');
            $table->string('journal_number', 60)->nullable();
            $table->dateTime('entry_date');
            $table->string('status', 30)->default('posted');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('reversal_of_journal_id')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'entry_type', 'source_type', 'source_id'], 'acct_journals_source_unique');
            $table->index(['tenant_id', 'company_id', 'entry_date'], 'acct_journals_date_idx');
        });

        Schema::create('accounting_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id')->index();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedInteger('line_no')->default(1);
            $table->string('account_code', 50);
            $table->string('account_name', 120);
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_period_locks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->date('locked_from');
            $table->date('locked_until');
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_id', 'locked_from', 'locked_until'], 'acct_period_locks_range_idx');
        });

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('module', 50);
            $table->string('action', 80);
            $table->string('subject_type', 120);
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_label')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('payload_hash', 64)->nullable();
            $table->json('payload')->nullable();
            $table->text('reason')->nullable();
            $table->text('decision_notes')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_id', 'module', 'action', 'status'], 'approval_requests_status_idx');
            $table->index(['tenant_id', 'company_id', 'subject_type', 'subject_id'], 'approval_requests_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('accounting_period_locks');
        Schema::dropIfExists('accounting_journal_lines');
        Schema::dropIfExists('accounting_journals');
    }
};
