<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['tenants', 'companies', 'branches'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            DB::statement(sprintf(
                "select setval(pg_get_serial_sequence('%s', 'id'), (select coalesce(max(id), 1) from %s))",
                $table,
                $table
            ));
        }
    }

    public function down(): void
    {
        // No-op. Sequence sync is safe and does not need rollback.
    }
};
