<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('whatsapp_instances')->cascadeOnDelete();
            $table->string('name');
            $table->json('categories')->nullable();
            $table->string('endpoint_uri')->nullable();
            $table->string('meta_flow_id')->nullable()->unique();
            $table->string('status')->default('draft');
            $table->string('json_version')->nullable();
            $table->string('data_api_version')->nullable();
            $table->json('validation_errors')->nullable();
            $table->json('health_status')->nullable();
            $table->text('flow_json')->nullable();
            $table->text('preview_url')->nullable();
            $table->timestamp('preview_expires_at')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_flows');
    }
};
