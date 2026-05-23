<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;

class SchemaInspector
{
    public static function supportsColumnAfter(): bool
    {
        return false;
    }

    public static function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return (bool) DB::selectOne(
                'select 1 from pg_indexes where schemaname = current_schema() and tablename = ? and indexname = ? limit 1',
                [$table, $indexName]
            );
        }

        if ($driver === 'sqlite') {
            $quotedTable = str_replace("'", "''", $table);
            $indexes = DB::select("PRAGMA index_list('{$quotedTable}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function foreignKeyExists(string $table, string $constraintName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return (bool) DB::selectOne(
                'select 1 from information_schema.table_constraints where table_schema = current_schema() and table_name = ? and constraint_name = ? and constraint_type = ? limit 1',
                [$table, $constraintName, 'FOREIGN KEY']
            );
        }

        if ($driver === 'sqlite') {
            $quotedTable = str_replace("'", "''", $table);
            $expectedColumn = preg_replace('/^'.preg_quote($table, '/').'_|_foreign$/', '', $constraintName);
            $foreignKeys = DB::select("PRAGMA foreign_key_list('{$quotedTable}')");

            foreach ($foreignKeys as $foreignKey) {
                if (($foreignKey->from ?? null) === $expectedColumn) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function columnExists(string $table, string $column): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return (bool) DB::selectOne(
                'select 1 from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ? limit 1',
                [$table, $column]
            );
        }

        if ($driver === 'sqlite') {
            $quotedTable = str_replace("'", "''", $table);
            $columns = DB::select("PRAGMA table_info('{$quotedTable}')");

            foreach ($columns as $tableColumn) {
                if (($tableColumn->name ?? null) === $column) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function primaryKeyStartsWith(string $table, string $column): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $row = DB::selectOne(
                'select a.attname
                 from pg_index i
                 join pg_class t on t.oid = i.indrelid
                 join pg_namespace n on n.oid = t.relnamespace
                 join unnest(i.indkey) with ordinality as cols(attnum, ord) on true
                 join pg_attribute a on a.attrelid = t.oid and a.attnum = cols.attnum
                 where i.indisprimary = true
                   and n.nspname = current_schema()
                   and t.relname = ?
                 order by cols.ord
                 limit 1',
                [$table]
            );

            return ($row->attname ?? null) === $column;
        }

        if ($driver === 'sqlite') {
            $quotedTable = str_replace("'", "''", $table);
            $columns = DB::select("PRAGMA table_info('{$quotedTable}')");
            $primaryColumns = collect($columns)
                ->filter(fn ($tableColumn) => (int) ($tableColumn->pk ?? 0) > 0)
                ->sortBy(fn ($tableColumn) => (int) $tableColumn->pk)
                ->values();

            return ($primaryColumns->first()->name ?? null) === $column;
        }

        return false;
    }
}
