<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('email_campaigns')->cascadeOnDelete();
            $table->enum('type', ['static', 'dynamic']);
            $table->string('filename');
            $table->string('path')->nullable(); // only for static
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->longText('template_html')->nullable(); // for dynamic
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};
