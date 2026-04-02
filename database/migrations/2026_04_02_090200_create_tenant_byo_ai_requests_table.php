<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_byo_ai_requests')) {
            return;
        }

        Schema::create('tenant_byo_ai_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->string('preferred_provider', 50)->nullable();
            $table->string('intended_volume', 100)->nullable();
            $table->unsignedInteger('chatbot_account_count')->nullable();
            $table->unsignedInteger('channel_count')->nullable();
            $table->string('technical_contact_name')->nullable();
            $table->string('technical_contact_email')->nullable();
            $table->text('notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_byo_ai_requests');
    }
};
