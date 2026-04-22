<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_tax_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->nullableMorphs('source_document');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->foreignId('finance_tax_rate_id')->nullable()->constrained('finance_tax_rates')->nullOnDelete();
            $table->string('document_type', 30);
            $table->string('document_status', 30)->default('draft');
            $table->string('document_number', 100)->nullable();
            $table->string('external_document_number', 100)->nullable();
            $table->date('transaction_date')->nullable();
            $table->date('document_date');
            $table->unsignedSmallInteger('tax_period_month');
            $table->unsignedSmallInteger('tax_period_year');
            $table->string('counterparty_name_snapshot', 255)->nullable();
            $table->string('counterparty_tax_id_snapshot', 100)->nullable();
            $table->string('counterparty_tax_name_snapshot', 255)->nullable();
            $table->text('counterparty_tax_address_snapshot')->nullable();
            $table->decimal('taxable_base', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('withheld_amount', 18, 2)->default(0);
            $table->string('currency_code', 10)->default('IDR');
            $table->text('reference_note')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_id', 'document_type', 'document_status'], 'finance_tax_documents_scope_status_idx');
            $table->index(['tenant_id', 'company_id', 'tax_period_year', 'tax_period_month'], 'finance_tax_documents_period_idx');
            $table->index(['tenant_id', 'company_id', 'document_number'], 'finance_tax_documents_number_idx');
            $table->index(['tenant_id', 'company_id', 'source_document_type', 'source_document_id'], 'finance_tax_documents_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_tax_documents');
    }
};
