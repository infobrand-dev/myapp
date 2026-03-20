<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('transaction_type', 20);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'slug']);
            $table->index(['tenant_id', 'company_id', 'transaction_type', 'is_active', 'name']);
        });

        DB::table('finance_categories')->insert([
            ['tenant_id' => 1, 'company_id' => 1, 'name' => 'General Cash In', 'slug' => 'general-cash-in', 'transaction_type' => 'cash_in', 'is_active' => true, 'notes' => 'Pemasukan kas operasional umum.', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => 1, 'company_id' => 1, 'name' => 'General Cash Out', 'slug' => 'general-cash-out', 'transaction_type' => 'cash_out', 'is_active' => true, 'notes' => 'Pengeluaran kas operasional umum.', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => 1, 'company_id' => 1, 'name' => 'Expense', 'slug' => 'expense', 'transaction_type' => 'expense', 'is_active' => true, 'notes' => 'Cash out untuk biaya operasional ringan.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_categories');
    }
};
