<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->nullable()->constrained('whatsapp_instances')->nullOnDelete();
            $table->string('provider', 30)->default('cloud');
            $table->string('event_key', 64)->unique();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->string('process_status', 20)->default('pending'); // pending|processed|failed|ignored
            $table->unsignedInteger('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'process_status']);
            $table->index(['instance_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_events');
    }
};

