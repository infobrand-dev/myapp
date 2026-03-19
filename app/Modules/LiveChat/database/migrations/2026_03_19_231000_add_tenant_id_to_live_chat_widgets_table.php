<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('live_chat_widgets')) {
            return;
        }

        Schema::table('live_chat_widgets', function (Blueprint $table) {
            if (!Schema::hasColumn('live_chat_widgets', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('live_chat_widgets')) {
            return;
        }

        Schema::table('live_chat_widgets', function (Blueprint $table) {
            if (Schema::hasColumn('live_chat_widgets', 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }
        });
    }
};
