<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection($this->connection)->create('storage_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 120);
            $table->string('driver', 20)->default('s3');
            $table->string('visibility_scope', 20)->default('private')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->unsignedInteger('weight')->default(100);
            $table->unsignedInteger('priority')->default(100);
            $table->string('failure_mode', 30)->default('mark_unreachable');
            $table->json('purposes')->nullable();
            $table->string('bucket', 120)->nullable();
            $table->string('region', 60)->nullable();
            $table->string('endpoint', 255)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('root_path', 255)->nullable();
            $table->string('access_key_id', 255)->nullable();
            $table->text('secret_access_key')->nullable();
            $table->boolean('use_path_style_endpoint')->default(false);
            $table->timestamp('last_read_failed_at')->nullable();
            $table->timestamp('last_write_failed_at')->nullable();
            $table->text('last_error_summary')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['visibility_scope', 'is_active', 'priority'], 'storage_profiles_scope_active_priority_idx');
        });

        $profiles = [
            [
                'code' => 'public-default-profile',
                'name' => 'Public Default Profile',
                'driver' => (string) config('filesystems.disks.' . config('workspace-files.public_disk', 'public') . '.driver', 'local'),
                'visibility_scope' => 'public',
                'is_active' => DB::raw('true'),
                'is_default' => DB::raw('true'),
                'weight' => 100,
                'priority' => 100,
                'failure_mode' => 'mark_unreachable',
                'purposes' => json_encode(['public_asset']),
                'bucket' => config('filesystems.disks.' . config('workspace-files.public_disk', 'public') . '.bucket'),
                'region' => config('filesystems.disks.' . config('workspace-files.public_disk', 'public') . '.region'),
                'endpoint' => config('filesystems.disks.' . config('workspace-files.public_disk', 'public') . '.endpoint'),
                'url' => config('filesystems.disks.' . config('workspace-files.public_disk', 'public') . '.url'),
                'root_path' => config('filesystems.disks.' . config('workspace-files.public_disk', 'public') . '.root'),
                'access_key_id' => config('filesystems.disks.' . config('workspace-files.public_disk', 'public') . '.key'),
                'secret_access_key' => config('filesystems.disks.' . config('workspace-files.public_disk', 'public') . '.secret'),
                'use_path_style_endpoint' => config('filesystems.disks.' . config('workspace-files.public_disk', 'public') . '.use_path_style_endpoint', false)
                    ? DB::raw('true')
                    : DB::raw('false'),
                'meta' => json_encode(['bootstrapped' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'private-default-profile',
                'name' => 'Private Default Profile',
                'driver' => (string) config('filesystems.disks.' . config('workspace-files.private_disk', 'private') . '.driver', 'local'),
                'visibility_scope' => 'private',
                'is_active' => DB::raw('true'),
                'is_default' => DB::raw('true'),
                'weight' => 100,
                'priority' => 100,
                'failure_mode' => 'mark_unreachable',
                'purposes' => json_encode([
                    'finance_attachment',
                    'payment_proof',
                    'bank_statement',
                    'sales_attachment',
                ]),
                'bucket' => config('filesystems.disks.' . config('workspace-files.private_disk', 'private') . '.bucket'),
                'region' => config('filesystems.disks.' . config('workspace-files.private_disk', 'private') . '.region'),
                'endpoint' => config('filesystems.disks.' . config('workspace-files.private_disk', 'private') . '.endpoint'),
                'url' => config('filesystems.disks.' . config('workspace-files.private_disk', 'private') . '.url'),
                'root_path' => config('filesystems.disks.' . config('workspace-files.private_disk', 'private') . '.root'),
                'access_key_id' => config('filesystems.disks.' . config('workspace-files.private_disk', 'private') . '.key'),
                'secret_access_key' => config('filesystems.disks.' . config('workspace-files.private_disk', 'private') . '.secret'),
                'use_path_style_endpoint' => config('filesystems.disks.' . config('workspace-files.private_disk', 'private') . '.use_path_style_endpoint', false)
                    ? DB::raw('true')
                    : DB::raw('false'),
                'meta' => json_encode(['bootstrapped' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::connection($this->connection)->table('storage_profiles')->insert($profiles);
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('storage_profiles');
    }
};
