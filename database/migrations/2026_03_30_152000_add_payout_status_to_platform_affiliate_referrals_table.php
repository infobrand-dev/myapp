<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_affiliate_referrals', function (Blueprint $table) {
            $table->string('payout_status', 30)->default('unqualified')->after('commission_amount');
            $table->timestamp('approved_at')->nullable()->after('converted_at');
            $table->timestamp('paid_at')->nullable()->after('approved_at');
            $table->string('payout_reference', 100)->nullable()->after('paid_at');
            $table->text('payout_notes')->nullable()->after('payout_reference');

            $table->index(['platform_affiliate_id', 'payout_status'], 'platform_affiliate_referrals_payout_idx');
        });
    }

    public function down(): void
    {
        Schema::table('platform_affiliate_referrals', function (Blueprint $table) {
            $table->dropIndex('platform_affiliate_referrals_payout_idx');
            $table->dropColumn([
                'payout_status',
                'approved_at',
                'paid_at',
                'payout_reference',
                'payout_notes',
            ]);
        });
    }
};
