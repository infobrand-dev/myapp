<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $databasePayload = [
            'server_id' => $serverId ?? null,
            'database_name' => env('CENTRAL_DB_DATABASE', env('DB_DATABASE', 'forge')),
            'connection_name' => 'tenant',
            'username' => env('CENTRAL_DB_USERNAME', env('DB_USERNAME')),
            'password' => ($password = env('CENTRAL_DB_PASSWORD', env('DB_PASSWORD'))) ? Crypt::encryptString((string) $password) : null,
            'status' => 'active',
            'sslmode' => env('CENTRAL_DB_SSLMODE', env('DB_SSLMODE', 'prefer')),
            'max_schemas' => 1000,
            'current_schemas' => 0,
            'schema_prefix' => 'tenant_',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $serverId = DB::table('tenant_servers')->insertGetId([
            'key' => 'primary',
            'host' => env('CENTRAL_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => (int) env('CENTRAL_DB_PORT', env('DB_PORT', 5432)),
            'region' => env('APP_REGION'),
            'provider' => env('DB_PROVIDER', 'postgresql'),
            'status' => 'active',
            'max_tenants' => 1000,
            'current_tenants' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $databasePayload['server_id'] = $serverId;

        if (Schema::hasColumn('tenant_databases', 'key')) {
            $databasePayload['key'] = 'main';
        }

        $databaseId = DB::table('tenant_databases')->insertGetId($databasePayload);

        Schema::table('tenants', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->string('status')->default('active')->after('slug');
            $table->string('plan')->nullable()->after('status');
            $table->foreignId('server_id')->nullable()->after('plan')->constrained('tenant_servers')->nullOnDelete();
            $table->foreignId('database_id')->nullable()->after('server_id')->constrained('tenant_databases')->nullOnDelete();
            $table->string('schema_name')->nullable()->after('database_id');
        });

        DB::table('tenants')
            ->orderBy('id')
            ->get(['id', 'slug', 'is_active'])
            ->each(function (object $tenant) use ($serverId, $databaseId): void {
                DB::table('tenants')
                    ->where('id', $tenant->id)
                    ->update([
                        'uuid' => (string) Str::uuid(),
                        'status' => $tenant->is_active ? 'active' : 'inactive',
                        'server_id' => $serverId,
                        'database_id' => $databaseId,
                        'schema_name' => $tenant->id === 1 ? 'public' : 'tenant_' . trim(strtolower(preg_replace('/[^a-z0-9_]/', '_', str_replace('-', '_', (string) $tenant->slug))), '_'),
                    ]);
            });

        DB::table('tenant_servers')
            ->where('id', $serverId)
            ->update(['current_tenants' => DB::table('tenants')->count()]);

        DB::table('tenant_databases')
            ->where('id', $databaseId)
            ->update(['current_schemas' => DB::table('tenants')->count()]);

        Schema::table('tenants', function (Blueprint $table) {
            $table->unique('uuid');
            $table->index(['database_id', 'schema_name']);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['database_id', 'schema_name']);
            $table->dropUnique(['uuid']);
            $table->dropConstrainedForeignId('database_id');
            $table->dropConstrainedForeignId('server_id');
            $table->dropColumn(['uuid', 'status', 'plan', 'schema_name']);
        });
    }
};
