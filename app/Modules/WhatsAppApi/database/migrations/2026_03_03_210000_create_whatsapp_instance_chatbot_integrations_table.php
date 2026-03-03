<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_instance_chatbot_integrations')) {
            return;
        }

        Schema::create('whatsapp_instance_chatbot_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')
                ->constrained('whatsapp_instances')
                ->cascadeOnDelete();
            $table->boolean('auto_reply')->default(false);
            $table->unsignedBigInteger('chatbot_account_id')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique('instance_id');
            $table->index('chatbot_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_instance_chatbot_integrations');
    }
};

