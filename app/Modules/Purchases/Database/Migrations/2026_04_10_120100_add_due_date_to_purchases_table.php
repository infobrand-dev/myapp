<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('purchase_date');
            $table->index(['tenant_id', 'company_id', 'branch_id', 'due_date'], 'purchases_due_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_due_date_index');
            $table->dropColumn('due_date');
        });
    }
};
