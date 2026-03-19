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
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('transaction_type', 20);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['transaction_type', 'is_active']);
        });

        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->string('transaction_type', 20);
            $table->dateTime('transaction_date');
            $table->decimal('amount', 18, 2);
            $table->foreignId('finance_category_id')->constrained('finance_categories')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('outlet_id')->nullable();
            $table->unsignedBigInteger('pos_cash_session_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['transaction_type', 'transaction_date']);
            $table->index(['finance_category_id', 'transaction_date']);
            $table->index(['created_by', 'transaction_date']);
            $table->index(['outlet_id', 'transaction_date']);
            $table->index(['pos_cash_session_id', 'transaction_date']);
        });

        if (Schema::hasTable('pos_cash_sessions')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                $table->foreign('pos_cash_session_id')->references('id')->on('pos_cash_sessions')->nullOnDelete();
            });
        }

        DB::table('finance_categories')->insert([
            ['name' => 'General Cash In', 'slug' => 'general-cash-in', 'transaction_type' => 'cash_in', 'is_active' => true, 'notes' => 'Pemasukan kas operasional umum.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'General Cash Out', 'slug' => 'general-cash-out', 'transaction_type' => 'cash_out', 'is_active' => true, 'notes' => 'Pengeluaran kas operasional umum.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Expense', 'slug' => 'expense', 'transaction_type' => 'expense', 'is_active' => true, 'notes' => 'Cash out untuk biaya operasional ringan.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('finance_categories');
    }
};
