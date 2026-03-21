<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('document_settings')) {
            return;
        }

        if (!$this->indexExists('document_settings', 'document_settings_tenant_id_company_id_index')) {
            Schema::table('document_settings', function (Blueprint $table) {
                $table->index(['tenant_id', 'company_id'], 'document_settings_tenant_id_company_id_index');
            });
        }

        if (!$this->indexExists('document_settings', 'document_settings_branch_id_foreign')) {
            Schema::table('document_settings', function (Blueprint $table) {
                $table->index('branch_id', 'document_settings_branch_id_foreign');
            });
        }

        if (!$this->foreignKeyExists('document_settings', 'document_settings_company_id_foreign')) {
            Schema::table('document_settings', function (Blueprint $table) {
                $table->foreign('company_id', 'document_settings_company_id_foreign')
                    ->references('id')
                    ->on('companies')
                    ->cascadeOnDelete();
            });
        }

        if (!$this->foreignKeyExists('document_settings', 'document_settings_branch_id_foreign')) {
            Schema::table('document_settings', function (Blueprint $table) {
                $table->foreign('branch_id', 'document_settings_branch_id_foreign')
                    ->references('id')
                    ->on('branches')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('document_settings')) {
            return;
        }

        if ($this->foreignKeyExists('document_settings', 'document_settings_branch_id_foreign')) {
            Schema::table('document_settings', function (Blueprint $table) {
                $table->dropForeign('document_settings_branch_id_foreign');
            });
        }

        if ($this->foreignKeyExists('document_settings', 'document_settings_company_id_foreign')) {
            Schema::table('document_settings', function (Blueprint $table) {
                $table->dropForeign('document_settings_company_id_foreign');
            });
        }

        if ($this->indexExists('document_settings', 'document_settings_tenant_id_company_id_index')) {
            Schema::table('document_settings', function (Blueprint $table) {
                $table->dropIndex('document_settings_tenant_id_company_id_index');
            });
        }

        if ($this->indexExists('document_settings', 'document_settings_branch_id_foreign')) {
            Schema::table('document_settings', function (Blueprint $table) {
                $table->dropIndex('document_settings_branch_id_foreign');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
