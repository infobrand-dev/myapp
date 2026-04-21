<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('tax_type', 30)->default('sales');
            $table->decimal('rate_percent', 8, 4)->default(0);
            $table->boolean('is_inclusive')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('sales_account_code', 100)->nullable();
            $table->string('purchase_account_code', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'code']);
            $table->index(['tenant_id', 'company_id', 'tax_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_tax_rates');
    }
};
