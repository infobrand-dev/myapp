<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stored_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('disk', 50);
            $table->string('directory', 150)->nullable();
            $table->string('path', 500);
            $table->string('visibility', 20)->default('private');
            $table->string('category', 60)->index();
            $table->string('origin_system', 80)->nullable();
            $table->string('origin_owner', 30)->nullable();
            $table->string('source_module', 80)->nullable();
            $table->string('source_context', 120)->nullable();
            $table->string('storage_driver', 30)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('content_hash', 64)->nullable()->index();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'category', 'created_at'], 'stored_files_tenant_category_created_idx');
        });

        Schema::create('stored_file_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stored_file_id')->constrained('stored_files')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action', 30);
            $table->boolean('was_authorized')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['stored_file_id', 'created_at'], 'stored_file_access_logs_file_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_file_access_logs');
        Schema::dropIfExists('stored_files');
    }
};
