<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finance_transactions')) {
            Schema::create('finance_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->unsignedBigInteger('company_id')->default(1)->index();
                $table->string('transaction_number', 50);
                $table->string('transaction_type', 20);
                $table->dateTime('transaction_date');
                $table->decimal('amount', 18, 2);
                $table->foreignId('finance_category_id')->constrained('finance_categories')->restrictOnDelete();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('pos_cash_session_id')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'company_id', 'transaction_number']);
                $table->index(['tenant_id', 'company_id', 'transaction_date']);
                $table->index(['tenant_id', 'company_id', 'transaction_type', 'transaction_date']);
                $table->index(['tenant_id', 'company_id', 'finance_category_id', 'transaction_date']);
                $table->index(['tenant_id', 'company_id', 'created_by', 'transaction_date']);
                $table->index(['tenant_id', 'company_id', 'branch_id', 'transaction_date']);
                $table->index(['tenant_id', 'company_id', 'pos_cash_session_id', 'transaction_date']);
            });
        }

        $this->ensureOptionalPosCashSessionForeignKey();
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }

    private function ensureOptionalPosCashSessionForeignKey(): void
    {
        if (!Schema::hasTable('pos_cash_sessions') || !Schema::hasTable('finance_transactions')) {
            return;
        }

        if ($this->foreignKeyExists('finance_transactions', 'finance_transactions_pos_cash_session_id_foreign')) {
            return;
        }

        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->foreign('pos_cash_session_id')
                ->references('id')
                ->on('pos_cash_sessions')
                ->nullOnDelete();
        });
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        if (DB::getDriverName() === 'pgsql') {
            return (bool) DB::table('information_schema.table_constraints')
                ->where('table_schema', 'public')
                ->where('table_name', $table)
                ->where('constraint_name', $foreignKey)
                ->where('constraint_type', 'FOREIGN KEY')
                ->exists();
        }

        return !empty(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $foreignKey, 'FOREIGN KEY']
        ));
    }
};
