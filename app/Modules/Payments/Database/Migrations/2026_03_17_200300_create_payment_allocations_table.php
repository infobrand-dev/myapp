<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->morphs('payable');
            $table->unsignedInteger('allocation_order')->default(1);
            $table->decimal('amount', 18, 2);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'payment_id', 'allocation_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
