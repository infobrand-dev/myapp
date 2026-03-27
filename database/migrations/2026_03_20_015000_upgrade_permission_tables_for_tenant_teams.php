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

        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'tenant_id';

        $this->upgradeRolesTable($tableNames['roles'], $teamForeignKey);
        $this->upgradePivotTable($tableNames['model_has_roles'], $teamForeignKey, 'role_id');
        $this->upgradePivotTable($tableNames['model_has_permissions'], $teamForeignKey, 'permission_id');
    }

    public function down(): void
    {
        // Intentionally not reversed. This migration upgrades legacy permission tables in-place.
    }

    private function upgradeRolesTable(string $table, string $teamForeignKey): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $supportsAfter = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

        if (!Schema::hasColumn($table, $teamForeignKey)) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($teamForeignKey, $supportsAfter) {
                $column = $tableBlueprint->unsignedBigInteger($teamForeignKey)->nullable();

                if ($supportsAfter) {
                    $column->after('id');
                }
            });

            DB::table($table)
                ->whereNull($teamForeignKey)
                ->update([$teamForeignKey => 1]);

            Schema::table($table, function (Blueprint $tableBlueprint) use ($teamForeignKey) {
                $tableBlueprint->index($teamForeignKey, 'roles_team_foreign_key_index');
            });
        }

        try {
            Schema::table($table, function (Blueprint $tableBlueprint) {
                $tableBlueprint->dropUnique('roles_name_guard_name_unique');
            });
        } catch (Throwable $e) {
        }

        if (!$this->indexExists($table, 'roles_tenant_id_name_guard_name_unique')) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($teamForeignKey) {
                $tableBlueprint->unique([$teamForeignKey, 'name', 'guard_name'], 'roles_tenant_id_name_guard_name_unique');
            });
        }
    }

    private function upgradePivotTable(string $table, string $teamForeignKey, string $pivotKey): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $supportsAfter = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

        if (!Schema::hasColumn($table, $teamForeignKey)) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($teamForeignKey, $supportsAfter) {
                $column = $tableBlueprint->unsignedBigInteger($teamForeignKey)->default(1);

                if ($supportsAfter) {
                    $column->after('model_id');
                }
            });

            if (!$this->indexExists($table, $table . '_team_foreign_key_index')) {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($teamForeignKey, $table) {
                    $tableBlueprint->index($teamForeignKey, $table . '_team_foreign_key_index');
                });
            }
        }

        if ($this->primaryStartsWith($table, $teamForeignKey)) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('ALTER TABLE `' . $table . '` DROP PRIMARY KEY');
        DB::statement(
            'ALTER TABLE `' . $table . '` ADD PRIMARY KEY (`' . $teamForeignKey . '`, `' . $pivotKey . '`, `model_id`, `model_type`)'
        );
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists();
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->exists();
        }

        return false;
    }

    private function primaryStartsWith(string $table, string $column): bool
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $firstColumn = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', 'PRIMARY')
            ->orderBy('seq_in_index')
            ->value('column_name');

        return $firstColumn === $column;
    }
};
