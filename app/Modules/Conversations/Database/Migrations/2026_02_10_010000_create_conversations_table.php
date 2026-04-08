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
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('instance_id')->nullable();
            $table->string('channel')->default('internal');
            $table->string('external_id')->nullable();
            $table->string('contact_external_id')->nullable();
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

            $table->unique(['tenant_id', 'channel', 'instance_id', 'contact_external_id'], 'conversations_tenant_channel_instance_contact_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
