<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xendit_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->string('environment', 20)->default('sandbox');
            $table->text('secret_key')->nullable();
            $table->text('webhook_token')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xendit_settings');
    }
};
