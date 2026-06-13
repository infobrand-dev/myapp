<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_servers', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('region')->nullable();
            $table->string('base_url')->nullable();
            $table->string('queue_cluster')->default('default');
            $table->string('realtime_cluster')->default('default');
            $table->string('scheduler_cluster')->default('default');
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_servers', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('driver')->default('local');
            $table->string('provider')->nullable();
            $table->string('region')->nullable();
            $table->string('endpoint')->nullable();
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_buckets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_server_id')->constrained('storage_servers')->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->string('disk')->default('public');
            $table->string('visibility')->default('public');
            $table->string('region')->nullable();
            $table->string('base_path')->default('/');
            $table->string('cdn_url')->nullable();
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['storage_server_id', 'key']);
        });

        Schema::create('tenant_runtime_topologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('app_server_id')->nullable()->constrained('app_servers')->nullOnDelete();
            $table->string('app_server_key');
            $table->string('queue_cluster')->default('default');
            $table->string('realtime_cluster')->default('default');
            $table->string('scheduler_cluster')->default('default');
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique('tenant_id');
        });

        Schema::create('tenant_storage_topologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('storage_server_id')->nullable()->constrained('storage_servers')->nullOnDelete();
            $table->foreignId('storage_bucket_id')->nullable()->constrained('storage_buckets')->nullOnDelete();
            $table->string('storage_server_key');
            $table->string('storage_bucket_key');
            $table->string('disk')->default('public');
            $table->string('visibility')->default('public');
            $table->string('base_path')->default('/');
            $table->boolean('is_default')->default(true);
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'visibility', 'is_default']);
        });

        $appServerId = DB::table('app_servers')->insertGetId([
            'key' => 'primary-app',
            'name' => 'Primary App Cluster',
            'region' => env('APP_REGION'),
            'base_url' => env('APP_URL'),
            'queue_cluster' => 'default',
            'realtime_cluster' => 'default',
            'scheduler_cluster' => 'default',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $storageServerId = DB::table('storage_servers')->insertGetId([
            'key' => 'primary-storage',
            'name' => 'Primary Storage',
            'driver' => config('filesystems.disks.s3.driver', 'local'),
            'provider' => env('AWS_BUCKET') ? 's3' : 'local',
            'region' => env('AWS_DEFAULT_REGION'),
            'endpoint' => env('AWS_ENDPOINT'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $publicBucketId = DB::table('storage_buckets')->insertGetId([
            'storage_server_id' => $storageServerId,
            'key' => 'public-default',
            'name' => env('AWS_BUCKET', 'public'),
            'disk' => config('workspace-files.public_disk', 'public'),
            'visibility' => 'public',
            'region' => env('AWS_DEFAULT_REGION'),
            'base_path' => '/',
            'cdn_url' => env('AWS_URL'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $privateBucketId = DB::table('storage_buckets')->insertGetId([
            'storage_server_id' => $storageServerId,
            'key' => 'private-default',
            'name' => env('AWS_BUCKET', 'private'),
            'disk' => config('workspace-files.private_disk', 'private'),
            'visibility' => 'private',
            'region' => env('AWS_DEFAULT_REGION'),
            'base_path' => '/',
            'cdn_url' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenants = DB::table('tenants')->orderBy('id')->get(['id']);

        foreach ($tenants as $tenant) {
            DB::table('tenant_runtime_topologies')->insert([
                'tenant_id' => $tenant->id,
                'app_server_id' => $appServerId,
                'app_server_key' => 'primary-app',
                'queue_cluster' => 'default',
                'realtime_cluster' => 'default',
                'scheduler_cluster' => 'default',
                'status' => 'active',
                'meta' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('tenant_storage_topologies')->insert([
                [
                    'tenant_id' => $tenant->id,
                    'storage_server_id' => $storageServerId,
                    'storage_bucket_id' => $publicBucketId,
                    'storage_server_key' => 'primary-storage',
                    'storage_bucket_key' => 'public-default',
                    'disk' => config('workspace-files.public_disk', 'public'),
                    'visibility' => 'public',
                    'base_path' => 'tenants/' . $tenant->id . '/public',
                    'is_default' => DB::raw('true'),
                    'status' => 'active',
                    'meta' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tenant_id' => $tenant->id,
                    'storage_server_id' => $storageServerId,
                    'storage_bucket_id' => $privateBucketId,
                    'storage_server_key' => 'primary-storage',
                    'storage_bucket_key' => 'private-default',
                    'disk' => config('workspace-files.private_disk', 'private'),
                    'visibility' => 'private',
                    'base_path' => 'tenants/' . $tenant->id . '/private',
                    'is_default' => DB::raw('true'),
                    'status' => 'active',
                    'meta' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_storage_topologies');
        Schema::dropIfExists('tenant_runtime_topologies');
        Schema::dropIfExists('storage_buckets');
        Schema::dropIfExists('storage_servers');
        Schema::dropIfExists('app_servers');
    }
};
