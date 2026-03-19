<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('name');
            $table->string('subject');
            $table->enum('status', ['draft', 'scheduled', 'running', 'done'])->default('draft');
            $table->longText('body_html')->nullable();
            $table->json('filter_json')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
