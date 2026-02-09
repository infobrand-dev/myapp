<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_api_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('base_url')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('waba_id')->nullable();
            $table->text('access_token');
            $table->string('verify_token')->nullable();
            $table->string('default_sender_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('timeout_seconds')->default(30);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status')->nullable();
            $table->text('last_test_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_api_settings');
    }
};
