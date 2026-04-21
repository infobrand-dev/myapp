<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('code', 50);
            $table->string('name', 120);
            $table->string('account_type', 30);
            $table->string('normal_balance', 10)->default('debit');
            $table->string('report_section', 30)->default('balance_sheet');
            $table->boolean('is_postable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'code'], 'coa_company_code_unique');
            $table->index(['tenant_id', 'company_id', 'account_type', 'is_active'], 'coa_type_active_idx');
            $table->index(['tenant_id', 'company_id', 'report_section', 'is_active'], 'coa_section_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
