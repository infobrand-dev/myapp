<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_matrix_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('approval_matrix_rules', 'maker_checker_required')) {
                $table->boolean('maker_checker_required')->default(false)->after('required_approvals');
            }

            if (!Schema::hasColumn('approval_matrix_rules', 'max_backdate_days')) {
                $table->unsignedInteger('max_backdate_days')->nullable()->after('maker_checker_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('approval_matrix_rules', function (Blueprint $table) {
            foreach (['maker_checker_required', 'max_backdate_days'] as $column) {
                if (Schema::hasColumn('approval_matrix_rules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
