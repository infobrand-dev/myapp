<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('receipt_number', 50);
            $table->foreignId('inventory_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->string('fingerprint', 64)->nullable();
            $table->string('status', 30)->default('posted');
            $table->dateTime('receipt_date');
            $table->text('notes')->nullable();
            $table->decimal('total_received_qty', 18, 4)->default(0);
            $table->json('integration_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'receipt_number']);
            $table->unique(['tenant_id', 'fingerprint']);
            $table->index(['company_id', 'branch_id', 'purchase_id', 'receipt_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receipts');
    }
};
