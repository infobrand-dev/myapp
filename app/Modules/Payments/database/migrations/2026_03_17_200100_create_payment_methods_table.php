<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('code', 50);
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

            $table->unique(['tenant_id', 'company_id', 'code']);
            $table->index(['tenant_id', 'company_id', 'is_active', 'sort_order']);
            $table->index(['tenant_id', 'company_id', 'type', 'is_active']);
        });

        DB::table('payment_methods')->insert([
            ['tenant_id' => 1, 'company_id' => 1, 'code' => 'cash', 'name' => 'Cash', 'type' => 'cash', 'requires_reference' => false, 'is_active' => true, 'is_system' => true, 'sort_order' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => 1, 'company_id' => 1, 'code' => 'bank_transfer', 'name' => 'Bank Transfer', 'type' => 'bank_transfer', 'requires_reference' => true, 'is_active' => true, 'is_system' => true, 'sort_order' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => 1, 'company_id' => 1, 'code' => 'debit_card', 'name' => 'Debit Card', 'type' => 'debit_card', 'requires_reference' => true, 'is_active' => true, 'is_system' => true, 'sort_order' => 30, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => 1, 'company_id' => 1, 'code' => 'credit_card', 'name' => 'Credit Card', 'type' => 'credit_card', 'requires_reference' => true, 'is_active' => true, 'is_system' => true, 'sort_order' => 40, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => 1, 'company_id' => 1, 'code' => 'ewallet', 'name' => 'E-Wallet', 'type' => 'ewallet', 'requires_reference' => true, 'is_active' => true, 'is_system' => true, 'sort_order' => 50, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => 1, 'company_id' => 1, 'code' => 'qris', 'name' => 'QRIS', 'type' => 'qris', 'requires_reference' => true, 'is_active' => true, 'is_system' => true, 'sort_order' => 60, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => 1, 'company_id' => 1, 'code' => 'manual', 'name' => 'Custom / Manual', 'type' => 'manual', 'requires_reference' => false, 'is_active' => true, 'is_system' => true, 'sort_order' => 70, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
