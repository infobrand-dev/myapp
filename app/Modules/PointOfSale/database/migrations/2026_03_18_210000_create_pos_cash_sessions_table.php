<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_cash_sessions')) {
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
                $table->index(['tenant_id', 'company_id', 'cashier_user_id', 'status'], 'pos_cash_sessions_scope_cashier_status_idx');
                $table->index(['tenant_id', 'company_id', 'branch_id', 'status'], 'pos_cash_sessions_scope_branch_status_idx');
            });
        }

        $this->ensureIndexes();
        $this->ensureCartForeignKey();
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_cash_sessions');
    }

    private function ensureIndexes(): void
    {
        if (!$this->indexExists('pos_cash_sessions', 'pos_cash_sessions_scope_cashier_status_idx')) {
            Schema::table('pos_cash_sessions', function (Blueprint $table) {
                $table->index(['tenant_id', 'company_id', 'cashier_user_id', 'status'], 'pos_cash_sessions_scope_cashier_status_idx');
            });
        }

        if (!$this->indexExists('pos_cash_sessions', 'pos_cash_sessions_scope_branch_status_idx')) {
            Schema::table('pos_cash_sessions', function (Blueprint $table) {
                $table->index(['tenant_id', 'company_id', 'branch_id', 'status'], 'pos_cash_sessions_scope_branch_status_idx');
            });
        }
    }

    private function ensureCartForeignKey(): void
    {
        if (!Schema::hasTable('pos_carts') || !Schema::hasTable('pos_cash_sessions')) {
            return;
        }

        if ($this->foreignKeyExists('pos_carts', 'pos_carts_pos_cash_session_id_foreign')) {
            return;
        }

        Schema::table('pos_carts', function (Blueprint $table) {
            $table->foreign('pos_cash_session_id')
                ->references('id')
                ->on('pos_cash_sessions')
                ->nullOnDelete();
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
