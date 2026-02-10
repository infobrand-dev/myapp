<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_instance_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('whatsapp_instances')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('agent'); // agent|supervisor
            $table->timestamps();
            $table->unique(['instance_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_instance_user');
    }
};
