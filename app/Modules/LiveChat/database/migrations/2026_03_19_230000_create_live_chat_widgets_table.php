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
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('name');
            $table->string('website_name')->nullable();
            $table->string('widget_token', 100)->unique();
            $table->text('welcome_text')->nullable();
            $table->string('theme_color', 20)->default('#206bc4');
            $table->string('launcher_label', 40)->nullable();
            $table->string('position', 20)->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->string('header_bg_color', 20)->nullable();
            $table->string('visitor_bubble_color', 20)->nullable();
            $table->string('agent_bubble_color', 20)->nullable();
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
