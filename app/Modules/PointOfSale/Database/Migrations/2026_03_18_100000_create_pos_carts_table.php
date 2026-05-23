<?php

use App\Support\Database\SchemaInspector;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_carts')) {
            Schema::create('pos_carts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->uuid('uuid')->unique();
                $table->string('status', 30)->default('active');
                $table->foreignId('cashier_user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('pos_cash_session_id')->nullable();
                $table->unsignedBigInteger('register_id')->nullable();
                $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
                $table->string('customer_label')->nullable();
                $table->string('currency_code', 10)->default('IDR');
                $table->text('notes')->nullable();
                $table->unsignedInteger('item_count')->default(0);
                $table->decimal('subtotal', 18, 2)->default(0);
                $table->decimal('item_discount_total', 18, 2)->default(0);
                $table->decimal('order_discount_total', 18, 2)->default(0);
                $table->decimal('tax_total', 18, 2)->default(0);
                $table->decimal('grand_total', 18, 2)->default(0);
                $table->json('discount_snapshot')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('held_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedBigInteger('completed_sale_id')->nullable();
                $table->timestamps();

                $table->index(['cashier_user_id', 'status']);
                $table->index(['pos_cash_session_id', 'status']);
                $table->index(['register_id', 'status']);
                $table->index(['branch_id', 'status']);
            });
        }

        $this->ensurePosCashSessionForeignKey();
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_carts');
    }

    private function ensurePosCashSessionForeignKey(): void
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

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        return SchemaInspector::foreignKeyExists($table, $foreignKey);
    }
};
