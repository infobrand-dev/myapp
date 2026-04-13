<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('purchases', 'expected_receive_date')) {
                    $table->date('expected_receive_date')->nullable()->after('due_date');
                }

                if (!Schema::hasColumn('purchases', 'landed_cost_total')) {
                    $table->decimal('landed_cost_total', 18, 2)->default(0)->after('tax_total');
                }

                if (!Schema::hasColumn('purchases', 'supplier_bill_status')) {
                    $table->string('supplier_bill_status', 30)->default('pending')->after('payment_status');
                }

                if (!Schema::hasColumn('purchases', 'supplier_bill_received_at')) {
                    $table->date('supplier_bill_received_at')->nullable()->after('supplier_bill_status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                foreach (['supplier_bill_received_at', 'supplier_bill_status', 'landed_cost_total', 'expected_receive_date'] as $column) {
                    if (Schema::hasColumn('purchases', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
