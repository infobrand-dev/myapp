<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_databases', function (Blueprint $table) {
            $table->string('key')->nullable()->after('server_id');
        });

        DB::table('tenant_databases')
            ->whereNull('key')
            ->orderBy('server_id')
            ->orderBy('id')
            ->get(['id', 'server_id'])
            ->each(function ($database): void {
                $isFirstForServer = !DB::table('tenant_databases')
                    ->where('server_id', $database->server_id)
                    ->whereNotNull('key')
                    ->exists();

                DB::table('tenant_databases')
                    ->where('id', $database->id)
                    ->update([
                        'key' => $isFirstForServer ? 'main' : 'db_' . $database->id,
                    ]);
            });

        Schema::table('tenant_databases', function (Blueprint $table) {
            $table->unique(['server_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('tenant_databases', function (Blueprint $table) {
            $table->dropUnique(['server_id', 'key']);
            $table->dropColumn('key');
        });
    }
};
