<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('proof_file_path')->nullable()->after('external_reference');
            $table->string('reconciliation_status', 30)->default('unreconciled')->after('status');
            $table->dateTime('reconciled_at')->nullable()->after('voided_at');
            $table->foreignId('reconciled_by')->nullable()->after('voided_by')->constrained('users')->nullOnDelete();
            $table->index(['tenant_id', 'company_id', 'reconciliation_status', 'paid_at'], 'payments_reconciliation_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_reconciliation_status_idx');
            $table->dropConstrainedForeignId('reconciled_by');
            $table->dropColumn(['proof_file_path', 'reconciliation_status', 'reconciled_at']);
        });
    }
};
