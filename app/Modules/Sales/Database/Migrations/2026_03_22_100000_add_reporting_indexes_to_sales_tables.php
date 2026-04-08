<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'company_id', 'branch_id', 'status', 'transaction_date'],
                'sales_report_scope_status_date_idx'
            );
            $table->index(
                ['tenant_id', 'company_id', 'status', 'source', 'transaction_date'],
                'sales_report_status_source_date_idx'
            );
            $table->index(
                ['tenant_id', 'company_id', 'status', 'created_by', 'transaction_date'],
                'sales_report_status_cashier_date_idx'
            );
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index(
                ['sale_id', 'product_id', 'product_variant_id'],
                'sale_items_sale_product_variant_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('sale_items_sale_product_variant_idx');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_report_scope_status_date_idx');
            $table->dropIndex('sales_report_status_source_date_idx');
            $table->dropIndex('sales_report_status_cashier_date_idx');
        });
    }
};
