<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shortlinks', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('destination_url');
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('shortlink_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shortlink_id')->constrained('shortlinks')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shortlink_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shortlink_id')->constrained('shortlinks')->cascadeOnDelete();
            $table->foreignId('shortlink_code_id')->constrained('shortlink_codes')->cascadeOnDelete();
            $table->string('code_used');
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->text('referer')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('query')->nullable();
            $table->timestamps();

            $table->index('shortlink_id');
            $table->index('shortlink_code_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortlink_clicks');
        Schema::dropIfExists('shortlink_codes');
        Schema::dropIfExists('shortlinks');
    }
};
