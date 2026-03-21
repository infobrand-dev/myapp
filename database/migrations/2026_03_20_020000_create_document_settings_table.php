<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_settings')) {
            return;
        }

        Schema::create('document_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('invoice_prefix', 30)->nullable();
            $table->unsignedInteger('invoice_padding')->default(5);
            $table->unsignedBigInteger('invoice_next_number')->default(1);
            $table->string('invoice_last_period', 20)->nullable();
            $table->string('invoice_reset_period', 20)->nullable();
            $table->text('document_header')->nullable();
            $table->text('document_footer')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'branch_id']);
            $table->index(['tenant_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_settings');
    }
};
