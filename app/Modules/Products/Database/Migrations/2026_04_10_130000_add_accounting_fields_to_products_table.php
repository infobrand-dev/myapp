<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('default_supplier_contact_id')->nullable()->after('unit_id')->constrained('contacts')->nullOnDelete();
            $table->decimal('minimum_stock', 18, 4)->default(0)->after('sell_price');
            $table->decimal('reorder_point', 18, 4)->default(0)->after('minimum_stock');
            $table->index(['tenant_id', 'default_supplier_contact_id'], 'products_default_supplier_idx');
            $table->index(['tenant_id', 'track_stock', 'reorder_point'], 'products_reorder_point_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_default_supplier_idx');
            $table->dropIndex('products_reorder_point_idx');
            $table->dropConstrainedForeignId('default_supplier_contact_id');
            $table->dropColumn(['minimum_stock', 'reorder_point']);
        });
    }
};
