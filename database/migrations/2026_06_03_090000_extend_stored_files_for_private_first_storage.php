<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stored_files', function (Blueprint $table) {
            $table->string('access_class', 40)->default('private_document')->after('category')->index();
            $table->string('share_strategy', 40)->nullable()->after('access_class');
            $table->string('retention_class', 40)->nullable()->after('share_strategy');
            $table->string('provider_origin', 40)->nullable()->after('retention_class');
            $table->string('provider_media_id', 255)->nullable()->after('provider_origin');
            $table->string('provider_media_url', 1000)->nullable()->after('provider_media_id');
            $table->timestamp('expires_at')->nullable()->after('provider_media_url');
        });
    }

    public function down(): void
    {
        Schema::table('stored_files', function (Blueprint $table) {
            $table->dropColumn([
                'access_class',
                'share_strategy',
                'retention_class',
                'provider_origin',
                'provider_media_id',
                'provider_media_url',
                'expires_at',
            ]);
        });
    }
};
