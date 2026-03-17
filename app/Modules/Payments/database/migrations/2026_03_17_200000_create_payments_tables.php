<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('sequence_date', 8)->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('type', 50)->default('manual');
            $table->boolean('requires_reference')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('config')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['type', 'is_active']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency_code', 10)->default('IDR');
            $table->dateTime('paid_at');
            $table->string('status', 30)->default('posted');
            $table->string('source', 30)->default('backoffice');
            $table->string('channel', 50)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->string('external_reference', 100)->nullable();
            $table->unsignedBigInteger('outlet_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'paid_at']);
            $table->index(['payment_method_id', 'paid_at']);
            $table->index(['source', 'created_at']);
            $table->index(['received_by', 'paid_at']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->morphs('payable');
            $table->unsignedInteger('allocation_order')->default(1);
            $table->decimal('amount', 18, 2);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'allocation_order']);
        });

        Schema::create('payment_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('event', 50);
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['payment_id', 'created_at']);
        });

        Schema::create('payment_void_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('status_before', 30)->nullable();
            $table->text('reason');
            $table->json('snapshot')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('payment_methods')->insert([
            ['code' => 'cash', 'name' => 'Cash', 'type' => 'cash', 'requires_reference' => false, 'is_active' => true, 'is_system' => true, 'sort_order' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'bank_transfer', 'name' => 'Bank Transfer', 'type' => 'bank_transfer', 'requires_reference' => true, 'is_active' => true, 'is_system' => true, 'sort_order' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'debit_card', 'name' => 'Debit Card', 'type' => 'debit_card', 'requires_reference' => true, 'is_active' => true, 'is_system' => true, 'sort_order' => 30, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'credit_card', 'name' => 'Credit Card', 'type' => 'credit_card', 'requires_reference' => true, 'is_active' => true, 'is_system' => true, 'sort_order' => 40, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'ewallet', 'name' => 'E-Wallet', 'type' => 'ewallet', 'requires_reference' => true, 'is_active' => true, 'is_system' => true, 'sort_order' => 50, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'qris', 'name' => 'QRIS', 'type' => 'qris', 'requires_reference' => true, 'is_active' => true, 'is_system' => true, 'sort_order' => 60, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'manual', 'name' => 'Custom / Manual', 'type' => 'manual', 'requires_reference' => false, 'is_active' => true, 'is_system' => true, 'sort_order' => 70, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_void_logs');
        Schema::dropIfExists('payment_status_logs');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('payment_sequences');
    }
};
