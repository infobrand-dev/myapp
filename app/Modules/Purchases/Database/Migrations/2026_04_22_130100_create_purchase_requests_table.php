<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('request_number', 50);
            $table->unsignedBigInteger('contact_id')->nullable()->index();
            $table->string('supplier_name_snapshot')->nullable();
            $table->string('supplier_email_snapshot')->nullable();
            $table->string('supplier_phone_snapshot')->nullable();
            $table->text('supplier_address_snapshot')->nullable();
            $table->json('supplier_snapshot')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->dateTime('request_date')->index();
            $table->date('needed_by_date')->nullable()->index();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->dateTime('converted_at')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('landed_cost_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->string('currency_code', 10)->default('IDR');
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('totals_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('converted_purchase_order_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->unsignedBigInteger('converted_by')->nullable()->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'request_number']);
            $table->index(['tenant_id', 'company_id', 'branch_id', 'status', 'request_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
