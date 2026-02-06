<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_attachment_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('filename')->default('attachment.pdf');
            $table->longText('html');
            $table->string('mime')->default('application/pdf');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('email_attachment_campaign', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('email_campaigns')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('email_attachment_templates')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['campaign_id', 'template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachment_campaign');
        Schema::dropIfExists('email_attachment_templates');
    }
};
