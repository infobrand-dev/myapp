<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_folders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->foreignId('account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->string('remote_id')->nullable();
            $table->string('name');
            $table->string('type', 32)->default('custom');
            $table->boolean('is_selectable')->default(true);
            $table->unsignedBigInteger('last_uid')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'name']);
            $table->index(['tenant_id', 'account_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_folders');
    }
};
