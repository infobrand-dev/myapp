<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_statement_lines', 'resolution_reason')) {
                $table->string('resolution_reason', 120)->nullable()->after('match_status');
            }

            if (!Schema::hasColumn('bank_statement_lines', 'resolution_note')) {
                $table->text('resolution_note')->nullable()->after('resolution_reason');
            }

            if (!Schema::hasColumn('bank_statement_lines', 'resolved_at')) {
                $table->dateTime('resolved_at')->nullable()->after('matched_at');
            }

            if (!Schema::hasColumn('bank_statement_lines', 'resolved_by')) {
                $table->foreignId('resolved_by')->nullable()->after('matched_by')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            if (Schema::hasColumn('bank_statement_lines', 'resolved_by')) {
                $table->dropConstrainedForeignId('resolved_by');
            }

            $dropColumns = [];
            foreach (['resolution_reason', 'resolution_note', 'resolved_at'] as $column) {
                if (Schema::hasColumn('bank_statement_lines', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
