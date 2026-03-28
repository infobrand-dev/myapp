<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_invoice_items')) {
            return;
        }

        Schema::create('platform_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_invoice_id')->constrained('platform_invoices')->cascadeOnDelete();
            $table->string('item_type', 50);
            $table->string('item_code', 100)->nullable();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('total_price', 14, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['platform_invoice_id', 'item_type'], 'platform_invoice_items_invoice_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_invoice_items');
    }
};
