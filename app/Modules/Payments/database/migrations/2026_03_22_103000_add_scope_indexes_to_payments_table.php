<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'company_id', 'branch_id', 'status', 'paid_at'],
                'payments_scope_status_paid_at_idx'
            );
            $table->index(
                ['tenant_id', 'company_id', 'branch_id', 'received_by', 'paid_at'],
                'payments_scope_receiver_paid_at_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_scope_status_paid_at_idx');
            $table->dropIndex('payments_scope_receiver_paid_at_idx');
        });
    }
};
