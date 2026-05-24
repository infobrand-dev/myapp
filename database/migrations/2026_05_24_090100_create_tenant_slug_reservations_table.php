<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_slug_reservations')) {
            return;
        }

        Schema::create('tenant_slug_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('source', 50)->default('onboarding');
            $table->timestamp('reserved_until')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['slug', 'reserved_until', 'released_at'], 'tenant_slug_reservations_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_slug_reservations');
    }
};
