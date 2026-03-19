<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_presences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('manual_status', 20)->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['manual_status', 'last_heartbeat_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_presences');
    }
};
