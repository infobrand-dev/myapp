<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Conversations table already created by WhatsAppApi migration but ensure exists when that module disabled.
        if (!Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instance_id')->nullable()->constrained('whatsapp_instances')->nullOnDelete();
                $table->string('channel')->default('internal');
                $table->string('external_id')->nullable();
                $table->string('contact_wa_id')->nullable();
                $table->string('contact_name')->nullable();
                $table->string('status')->default('open');
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('claimed_at')->nullable();
                $table->timestamp('locked_until')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamp('last_incoming_at')->nullable();
                $table->timestamp('last_outgoing_at')->nullable();
                $table->unsignedInteger('unread_count')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['channel', 'instance_id', 'contact_wa_id']);
            });
        }

        if (!Schema::hasTable('conversation_participants')) {
            Schema::create('conversation_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role')->default('collaborator');
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('invited_at')->nullable();
                $table->timestamp('left_at')->nullable();
                $table->timestamps();
                $table->unique(['conversation_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('conversation_messages')) {
            Schema::create('conversation_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('direction')->default('in');
                $table->string('type')->default('text');
                $table->longText('body')->nullable();
                $table->string('media_url')->nullable();
                $table->string('media_mime')->nullable();
                $table->string('status')->default('queued');
                $table->string('wa_message_id')->nullable()->index();
                $table->string('error_message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['conversation_id', 'direction']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
