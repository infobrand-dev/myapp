<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_matrix_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('module', 50);
            $table->string('action', 80);
            $table->decimal('min_amount', 18, 2)->default(0);
            $table->unsignedInteger('required_approvals')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_id', 'module', 'action', 'is_active'], 'approval_matrix_rule_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_matrix_rules');
    }
};
