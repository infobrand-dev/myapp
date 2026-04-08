<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('sale_number', 50);
            $table->string('external_reference', 100)->nullable();
            $table->string('idempotency_payload_hash', 64)->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('customer_name_snapshot')->nullable();
            $table->string('customer_email_snapshot')->nullable();
            $table->string('customer_phone_snapshot', 50)->nullable();
            $table->text('customer_address_snapshot')->nullable();
            $table->json('customer_snapshot')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('payment_status', 30)->default('unpaid');
            $table->string('source', 30)->default('manual');
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('pos_cash_session_id')->nullable()->index();
            $table->dateTime('transaction_date')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('paid_total', 18, 2)->default(0);
            $table->decimal('balance_due', 18, 2)->default(0);
            $table->string('currency_code', 10)->default('IDR');
            $table->text('notes')->nullable();
            $table->text('void_reason')->nullable();
            $table->json('totals_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'sale_number']);
            $table->index(['tenant_id', 'company_id', 'status', 'transaction_date']);
            $table->index(['tenant_id', 'company_id', 'source', 'created_at']);
            $table->index(['tenant_id', 'company_id', 'payment_status', 'transaction_date']);
            $table->index(['tenant_id', 'company_id', 'contact_id', 'transaction_date']);
            $table->index(['tenant_id', 'company_id', 'created_by', 'transaction_date']);
            $table->index('idempotency_payload_hash');
            $table->index(['pos_cash_session_id', 'status']);
            $table->index(['tenant_id', 'company_id', 'branch_id', 'transaction_date']);
            $table->unique(['tenant_id', 'company_id', 'source', 'external_reference'], 'sales_source_external_reference_unique');

            if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
                $table->fullText(['customer_name_snapshot', 'notes', 'void_reason'], 'sales_search_fulltext');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
