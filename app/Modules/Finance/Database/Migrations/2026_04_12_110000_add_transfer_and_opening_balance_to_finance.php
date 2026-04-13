<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_accounts')) {
            Schema::table('finance_accounts', function (Blueprint $table) {
                if (!Schema::hasColumn('finance_accounts', 'opening_balance')) {
                    $table->decimal('opening_balance', 18, 2)->default(0)->after('account_number');
                }

                if (!Schema::hasColumn('finance_accounts', 'opening_balance_date')) {
                    $table->date('opening_balance_date')->nullable()->after('opening_balance');
                }
            });
        }

        if (Schema::hasTable('finance_transactions')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('finance_transactions', 'counterparty_finance_account_id')) {
                    $table->foreignId('counterparty_finance_account_id')->nullable()->after('finance_account_id')->constrained('finance_accounts')->nullOnDelete();
                }

                if (!Schema::hasColumn('finance_transactions', 'transfer_group_key')) {
                    $table->string('transfer_group_key', 80)->nullable()->after('counterparty_finance_account_id')->index();
                }

                if (!Schema::hasColumn('finance_transactions', 'transfer_pair_transaction_id')) {
                    $table->foreignId('transfer_pair_transaction_id')->nullable()->after('transfer_group_key')->constrained('finance_transactions')->nullOnDelete();
                }

                if (!Schema::hasColumn('finance_transactions', 'attachment_path')) {
                    $table->string('attachment_path')->nullable()->after('notes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('finance_transactions')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('finance_transactions', 'attachment_path')) {
                    $table->dropColumn('attachment_path');
                }

                if (Schema::hasColumn('finance_transactions', 'transfer_pair_transaction_id')) {
                    $table->dropConstrainedForeignId('transfer_pair_transaction_id');
                }

                if (Schema::hasColumn('finance_transactions', 'transfer_group_key')) {
                    $table->dropIndex(['transfer_group_key']);
                    $table->dropColumn('transfer_group_key');
                }

                if (Schema::hasColumn('finance_transactions', 'counterparty_finance_account_id')) {
                    $table->dropConstrainedForeignId('counterparty_finance_account_id');
                }
            });
        }

        if (Schema::hasTable('finance_accounts')) {
            Schema::table('finance_accounts', function (Blueprint $table) {
                if (Schema::hasColumn('finance_accounts', 'opening_balance_date')) {
                    $table->dropColumn('opening_balance_date');
                }

                if (Schema::hasColumn('finance_accounts', 'opening_balance')) {
                    $table->dropColumn('opening_balance');
                }
            });
        }
    }
};
