<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_instances', 'phone_number_id')) {
                $table->string('phone_number_id')->nullable()->after('phone_number');
            }
            if (!Schema::hasColumn('whatsapp_instances', 'cloud_business_account_id')) {
                $table->string('cloud_business_account_id')->nullable()->after('phone_number_id');
            }
            if (!Schema::hasColumn('whatsapp_instances', 'cloud_token')) {
                $table->text('cloud_token')->nullable()->after('cloud_business_account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_instances', 'cloud_token')) {
                $table->dropColumn('cloud_token');
            }
            if (Schema::hasColumn('whatsapp_instances', 'cloud_business_account_id')) {
                $table->dropColumn('cloud_business_account_id');
            }
            if (Schema::hasColumn('whatsapp_instances', 'phone_number_id')) {
                $table->dropColumn('phone_number_id');
            }
        });
    }
};
