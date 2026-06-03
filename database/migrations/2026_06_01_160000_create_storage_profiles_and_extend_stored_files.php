<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stored_files', function (Blueprint $table) {
            $table->unsignedBigInteger('storage_profile_id')->nullable()->after('branch_id')->index();
            $table->string('availability_status', 20)->default('available')->after('visibility')->index();
            $table->string('storage_bucket', 120)->nullable()->after('storage_driver');
            $table->string('storage_region', 60)->nullable()->after('storage_bucket');
            $table->string('storage_endpoint', 255)->nullable()->after('storage_region');
            $table->string('storage_url', 500)->nullable()->after('storage_endpoint');
            $table->string('storage_root', 255)->nullable()->after('storage_url');
            $table->json('storage_snapshot')->nullable()->after('storage_root');
        });
    }

    public function down(): void
    {
        Schema::table('stored_files', function (Blueprint $table) {
            $table->dropIndex(['storage_profile_id']);
            $table->dropColumn([
                'storage_profile_id',
                'availability_status',
                'storage_bucket',
                'storage_region',
                'storage_endpoint',
                'storage_url',
                'storage_root',
                'storage_snapshot',
            ]);
        });
    }
};
