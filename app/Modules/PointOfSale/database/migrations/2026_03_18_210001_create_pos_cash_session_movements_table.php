<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_cash_session_movements')) {
            Schema::create('pos_cash_session_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->unsignedBigInteger('company_id')->default(1)->index();
                $table->foreignId('cash_session_id')->constrained('pos_cash_sessions')->cascadeOnDelete();
                $table->string('movement_type', 20);
                $table->decimal('amount', 18, 2);
                $table->text('notes')->nullable();
                $table->timestamp('occurred_at');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'company_id', 'occurred_at'], 'pos_cash_movements_scope_occurred_idx');
                $table->index(['cash_session_id', 'occurred_at'], 'pos_cash_movements_session_occurred_idx');
            });
        }

        $this->ensureIndexes();
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_cash_session_movements');
    }

    private function ensureIndexes(): void
    {
        if (!$this->indexExists('pos_cash_session_movements', 'pos_cash_movements_scope_occurred_idx')) {
            Schema::table('pos_cash_session_movements', function (Blueprint $table) {
                $table->index(['tenant_id', 'company_id', 'occurred_at'], 'pos_cash_movements_scope_occurred_idx');
            });
        }

        if (!$this->indexExists('pos_cash_session_movements', 'pos_cash_movements_session_occurred_idx')) {
            Schema::table('pos_cash_session_movements', function (Blueprint $table) {
                $table->index(['cash_session_id', 'occurred_at'], 'pos_cash_movements_session_occurred_idx');
            });
        }
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
