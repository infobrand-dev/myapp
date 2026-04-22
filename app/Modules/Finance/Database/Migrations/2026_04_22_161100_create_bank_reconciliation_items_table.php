<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->morphs('reconcilable');
            $table->date('cleared_date');
            $table->decimal('cleared_amount', 18, 2)->default(0);
            $table->string('status', 30)->default('cleared');
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['bank_reconciliation_id', 'reconcilable_type', 'reconcilable_id'],
                'bank_reconciliation_items_unique'
            );
            $table->index(['tenant_id', 'company_id', 'reconcilable_type', 'reconcilable_id'], 'bank_reconciliation_items_reconcilable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_items');
    }
};
