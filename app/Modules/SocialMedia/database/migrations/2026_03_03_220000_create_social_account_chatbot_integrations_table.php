<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('social_account_chatbot_integrations')) {
            return;
        }

        Schema::create('social_account_chatbot_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('social_account_id');
            $table->boolean('auto_reply')->default(false);
            $table->unsignedBigInteger('chatbot_account_id')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique('social_account_id');
            $table->index('social_account_id');
            $table->index('chatbot_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_account_chatbot_integrations');
    }
};
