<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finance_accounts')) {
            Schema::create('finance_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->unsignedBigInteger('company_id')->default(1)->index();
                $table->string('name', 100);
                $table->string('slug', 120);
                $table->string('account_type', 20);
                $table->string('account_number', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['tenant_id', 'company_id', 'slug']);
                $table->index(['tenant_id', 'company_id', 'account_type', 'is_active'], 'finance_accounts_scope_type_active_idx');
                $table->index(['tenant_id', 'company_id', 'is_default'], 'finance_accounts_scope_default_idx');
            });
        }

        if (Schema::hasTable('finance_transactions') && !Schema::hasColumn('finance_transactions', 'finance_account_id')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                $table->foreignId('finance_account_id')->nullable()->after('amount')->constrained('finance_accounts')->nullOnDelete();
                $table->index(['tenant_id', 'company_id', 'finance_account_id', 'transaction_date'], 'finance_transactions_scope_account_date_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('finance_transactions') && Schema::hasColumn('finance_transactions', 'finance_account_id')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                $table->dropIndex('finance_transactions_scope_account_date_idx');
                $table->dropConstrainedForeignId('finance_account_id');
            });
        }

        Schema::dropIfExists('finance_accounts');
    }
};
