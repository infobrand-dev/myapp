<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_topologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('tenant_server_id')->nullable()->constrained('tenant_servers')->nullOnDelete();
            $table->foreignId('tenant_database_id')->nullable()->constrained('tenant_databases')->nullOnDelete();
            $table->string('server_key');
            $table->string('database_key');
            $table->string('schema_name')->default('public');
            $table->string('isolation_mode')->default('tenant_id');
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique('tenant_id');
            $table->index(['server_key', 'database_key', 'schema_name']);
            $table->index(['isolation_mode', 'status']);
        });

        $primaryServer = DB::table('tenant_servers')->where('key', 'primary')->first();
        $mainDatabase = DB::table('tenant_databases')->where('connection_name', 'tenant')->orderBy('id')->first();

        if ($primaryServer && $mainDatabase) {
            DB::table('tenant_topologies')->insert(
                DB::table('tenants')
                    ->orderBy('id')
                    ->get()
                    ->map(function ($tenant) use ($primaryServer, $mainDatabase): array {
                        return [
                            'tenant_id' => $tenant->id,
                            'tenant_server_id' => $primaryServer->id,
                            'tenant_database_id' => $mainDatabase->id,
                            'server_key' => 'primary',
                            'database_key' => 'main',
                            'schema_name' => 'public',
                            'isolation_mode' => 'tenant_id',
                            'status' => $tenant->is_active ? 'active' : 'inactive',
                            'meta' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })
                    ->all()
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_topologies');
    }
};
