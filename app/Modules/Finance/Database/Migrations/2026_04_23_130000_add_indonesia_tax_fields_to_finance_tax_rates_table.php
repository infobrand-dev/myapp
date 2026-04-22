<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_tax_rates', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_tax_rates', 'tax_scope')) {
                $table->string('tax_scope', 50)->default('general')->after('tax_type');
            }

            if (!Schema::hasColumn('finance_tax_rates', 'jurisdiction_code')) {
                $table->string('jurisdiction_code', 10)->default('ID')->after('tax_scope');
            }

            if (!Schema::hasColumn('finance_tax_rates', 'legal_basis')) {
                $table->string('legal_basis', 150)->nullable()->after('jurisdiction_code');
            }

            if (!Schema::hasColumn('finance_tax_rates', 'document_label')) {
                $table->string('document_label', 100)->nullable()->after('legal_basis');
            }

            if (!Schema::hasColumn('finance_tax_rates', 'requires_tax_number')) {
                $table->boolean('requires_tax_number')->default(false)->after('document_label');
            }

            if (!Schema::hasColumn('finance_tax_rates', 'requires_counterparty_tax_id')) {
                $table->boolean('requires_counterparty_tax_id')->default(false)->after('requires_tax_number');
            }

            if (!Schema::hasColumn('finance_tax_rates', 'withholding_account_code')) {
                $table->string('withholding_account_code', 100)->nullable()->after('purchase_account_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('finance_tax_rates', function (Blueprint $table) {
            foreach ([
                'withholding_account_code',
                'requires_counterparty_tax_id',
                'requires_tax_number',
                'document_label',
                'legal_basis',
                'jurisdiction_code',
                'tax_scope',
            ] as $column) {
                if (Schema::hasColumn('finance_tax_rates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
