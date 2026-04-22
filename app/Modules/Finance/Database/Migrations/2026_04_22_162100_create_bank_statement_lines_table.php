<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('bank_statement_import_id')->nullable()->constrained('bank_statement_imports')->nullOnDelete();
            $table->dateTime('transaction_date');
            $table->string('direction', 20);
            $table->decimal('amount', 18, 2);
            $table->string('reference_number', 120)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('external_key', 120)->nullable();
            $table->string('match_status', 30)->default('unmatched');
            $table->nullableMorphs('suggested_reconcilable');
            $table->unsignedInteger('match_score')->default(0);
            $table->nullableMorphs('matched_reconcilable');
            $table->dateTime('matched_at')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_id', 'bank_reconciliation_id', 'match_status'], 'bank_statement_lines_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
