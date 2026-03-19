<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_conditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->string('condition_type', 50);
            $table->string('operator', 20)->default('>=');
            $table->string('value_type', 20)->default('string');
            $table->string('value')->nullable();
            $table->decimal('secondary_value', 18, 4)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['discount_id', 'condition_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_conditions');
    }
};
