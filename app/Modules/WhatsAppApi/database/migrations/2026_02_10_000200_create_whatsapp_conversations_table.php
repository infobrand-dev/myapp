<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('whatsapp_instances')->cascadeOnDelete();
            $table->string('channel')->default('wa_api'); // wa_api|wa_bro|internal|social
            $table->string('external_id')->nullable(); // id from gateway if any
            $table->string('contact_wa_id');
            $table->string('contact_name')->nullable();
            $table->string('status')->default('open'); // open|pending|closed
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

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
