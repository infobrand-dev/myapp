<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_servers', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('host');
            $table->unsignedInteger('port')->default(5432);
            $table->string('region')->nullable();
            $table->string('provider')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('max_tenants')->default(1000);
            $table->unsignedInteger('current_tenants')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('tenant_servers')->cascadeOnDelete();
            $table->string('database_name');
            $table->string('connection_name')->default('tenant');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('status')->default('active');
            $table->string('sslmode')->default('prefer');
            $table->unsignedInteger('max_schemas')->default(1000);
            $table->unsignedInteger('current_schemas')->default(0);
            $table->string('schema_prefix')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();

            $table->unique(['server_id', 'database_name']);
        });

        Schema::create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_domains');
        Schema::dropIfExists('tenant_databases');
        Schema::dropIfExists('tenant_servers');
    }
};
