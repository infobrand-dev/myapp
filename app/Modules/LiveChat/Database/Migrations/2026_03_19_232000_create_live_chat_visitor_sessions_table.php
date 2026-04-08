<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('live_chat_visitor_sessions')) {
            return;
        }

        Schema::create('live_chat_visitor_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('live_chat_widget_id')->constrained('live_chat_widgets')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $table->string('visitor_key', 100);
            $table->string('session_token_hash', 64)->unique();
            $table->string('origin_host')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['live_chat_widget_id', 'visitor_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_chat_visitor_sessions');
    }
};
