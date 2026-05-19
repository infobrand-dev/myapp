<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_period_closings', function (Blueprint $table) {
            if (!Schema::hasColumn('accounting_period_closings', 'reopening_journal_id')) {
                $table->unsignedBigInteger('reopening_journal_id')->nullable()->after('closing_journal_id')->index();
            }

            if (!Schema::hasColumn('accounting_period_closings', 'reopened_by')) {
                $table->unsignedBigInteger('reopened_by')->nullable()->after('closed_by');
            }

            if (!Schema::hasColumn('accounting_period_closings', 'reopened_at')) {
                $table->timestamp('reopened_at')->nullable()->after('closed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounting_period_closings', function (Blueprint $table) {
            foreach (['reopening_journal_id', 'reopened_by', 'reopened_at'] as $column) {
                if (Schema::hasColumn('accounting_period_closings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
