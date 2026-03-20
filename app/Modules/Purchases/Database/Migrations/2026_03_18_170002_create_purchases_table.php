<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('purchase_number', 50);
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('supplier_name_snapshot')->nullable();
            $table->string('supplier_email_snapshot')->nullable();
            $table->string('supplier_phone_snapshot', 50)->nullable();
            $table->text('supplier_address_snapshot')->nullable();
            $table->json('supplier_snapshot')->nullable();
            $table->string('supplier_reference', 100)->nullable();
            $table->string('supplier_invoice_number', 100)->nullable();
            $table->text('supplier_notes')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('payment_status', 30)->default('unpaid');
            $table->dateTime('purchase_date')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('received_total_qty', 18, 4)->default(0);
            $table->decimal('paid_total', 18, 2)->default(0);
            $table->decimal('balance_due', 18, 2)->default(0);
            $table->string('currency_code', 10)->default('IDR');
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('void_reason')->nullable();
            $table->json('totals_snapshot')->nullable();
            $table->json('integration_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'purchase_number']);
            $table->index(['tenant_id', 'company_id', 'branch_id', 'status', 'purchase_date']);
            $table->index(['tenant_id', 'company_id', 'branch_id', 'payment_status', 'purchase_date']);
            $table->index(['tenant_id', 'company_id', 'branch_id', 'contact_id', 'purchase_date']);
            $table->index(['tenant_id', 'company_id', 'branch_id', 'created_by', 'purchase_date']);
            $table->index(['tenant_id', 'company_id', 'branch_id', 'supplier_invoice_number']);
            $table->index(['tenant_id', 'company_id', 'branch_id', 'supplier_reference']);
            $table->fullText(
                ['supplier_name_snapshot', 'supplier_notes', 'notes', 'internal_notes'],
                'purchases_search_fulltext'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
