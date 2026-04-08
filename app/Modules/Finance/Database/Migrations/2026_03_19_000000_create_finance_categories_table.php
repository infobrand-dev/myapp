<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finance_categories')) {
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
                $table->index(['tenant_id', 'company_id', 'transaction_type', 'is_active', 'name'], 'finance_categories_scope_type_active_name_idx');
            });
        }

        $this->ensureIndexes();
        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_categories');
    }

    private function seedDefaults(): void
    {
        $rows = [
            ['tenant_id' => 1, 'company_id' => 1, 'name' => 'General Cash In', 'slug' => 'general-cash-in', 'transaction_type' => 'cash_in', 'is_active' => true, 'notes' => 'Pemasukan kas operasional umum.'],
            ['tenant_id' => 1, 'company_id' => 1, 'name' => 'General Cash Out', 'slug' => 'general-cash-out', 'transaction_type' => 'cash_out', 'is_active' => true, 'notes' => 'Pengeluaran kas operasional umum.'],
            ['tenant_id' => 1, 'company_id' => 1, 'name' => 'Expense', 'slug' => 'expense', 'transaction_type' => 'expense', 'is_active' => true, 'notes' => 'Cash out untuk biaya operasional ringan.'],
        ];

        if (DB::getDriverName() === 'pgsql') {
            foreach ($rows as $row) {
                DB::statement(
                    'insert into finance_categories (tenant_id, company_id, name, slug, transaction_type, is_active, notes, created_at, updated_at)
                     values (?, ?, ?, ?, ?, true, ?, now(), now())
                     on conflict (tenant_id, company_id, slug) do nothing',
                    [$row['tenant_id'], $row['company_id'], $row['name'], $row['slug'], $row['transaction_type'], $row['notes']]
                );
            }

            return;
        }

        $timestamp = now();
        foreach ($rows as $row) {
            DB::table('finance_categories')->updateOrInsert(
                [
                    'tenant_id' => $row['tenant_id'],
                    'company_id' => $row['company_id'],
                    'slug' => $row['slug'],
                ],
                $row + ['created_at' => $timestamp, 'updated_at' => $timestamp]
            );
        }
    }

    private function ensureIndexes(): void
    {
        if ($this->indexExists('finance_categories', 'finance_categories_scope_type_active_name_idx')) {
            return;
        }

        Schema::table('finance_categories', function (Blueprint $table) {
            $table->index(['tenant_id', 'company_id', 'transaction_type', 'is_active', 'name'], 'finance_categories_scope_type_active_name_idx');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'pgsql') {
            return (bool) DB::table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists();
        }

        return !empty(DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$index]));
    }
};
