<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contacts')) {
            Schema::table('contacts', function (Blueprint $table) {
                if (!Schema::hasColumn('contacts', 'tax_name')) {
                    $table->string('tax_name')->nullable()->after('vat');
                }

                if (!Schema::hasColumn('contacts', 'tax_address')) {
                    $table->text('tax_address')->nullable()->after('shipping_address');
                }

                if (!Schema::hasColumn('contacts', 'tax_is_pkp')) {
                    $table->boolean('tax_is_pkp')->default(false)->after('tax_address');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contacts')) {
            Schema::table('contacts', function (Blueprint $table) {
                foreach (['tax_is_pkp', 'tax_address', 'tax_name'] as $column) {
                    if (Schema::hasColumn('contacts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
