<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('live_chat_widgets') && Schema::hasColumn('live_chat_widgets', 'tenant_id')) {
            DB::table('live_chat_widgets')->whereNull('tenant_id')->update(['tenant_id' => 1]);

            Schema::table('live_chat_widgets', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->default(1)->change();
            });
        }

        if (Schema::hasTable('live_chat_visitor_sessions') && Schema::hasColumn('live_chat_visitor_sessions', 'tenant_id')) {
            DB::table('live_chat_visitor_sessions')->whereNull('tenant_id')->update(['tenant_id' => 1]);

            Schema::table('live_chat_visitor_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->default(1)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('live_chat_widgets') && Schema::hasColumn('live_chat_widgets', 'tenant_id')) {
            Schema::table('live_chat_widgets', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->default(null)->change();
            });
        }

        if (Schema::hasTable('live_chat_visitor_sessions') && Schema::hasColumn('live_chat_visitor_sessions', 'tenant_id')) {
            Schema::table('live_chat_visitor_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->default(null)->change();
            });
        }
    }
};
