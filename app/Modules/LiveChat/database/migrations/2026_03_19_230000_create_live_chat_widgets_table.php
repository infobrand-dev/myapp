<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('live_chat_widgets')) {
            return;
        }

        Schema::create('live_chat_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('website_name')->nullable();
            $table->string('widget_token', 100)->unique();
            $table->text('welcome_text')->nullable();
            $table->string('theme_color', 20)->default('#206bc4');
            $table->json('allowed_domains')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_chat_widgets');
    }
};
