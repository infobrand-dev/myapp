<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('code', 50);
            $table->foreignId('cashier_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('status', 20)->default('active');
            $table->decimal('opening_cash_amount', 18, 2)->default(0);
            $table->text('opening_note')->nullable();
            $table->timestamp('opened_at');
            $table->decimal('closing_cash_amount', 18, 2)->nullable();
            $table->decimal('expected_cash_amount', 18, 2)->nullable();
            $table->decimal('difference_amount', 18, 2)->nullable();
            $table->text('closing_note')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'code']);
            $table->index(['tenant_id', 'company_id', 'cashier_user_id', 'status']);
            $table->index(['tenant_id', 'company_id', 'branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_cash_sessions');
    }
};
