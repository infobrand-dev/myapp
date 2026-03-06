<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_blast_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('wa_blast_campaigns')->cascadeOnDelete();
            $table->string('phone_number', 30);
            $table->string('contact_name')->nullable();
            $table->json('variables')->nullable(); // {"1":"value", "2":"value"}
            $table->string('status', 30)->default('pending'); // pending|processing|queued|sent|failed|skipped
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'phone_number']);
            $table->index(['campaign_id', 'status']);
            $table->index('conversation_id');
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_blast_recipients');
    }
};
