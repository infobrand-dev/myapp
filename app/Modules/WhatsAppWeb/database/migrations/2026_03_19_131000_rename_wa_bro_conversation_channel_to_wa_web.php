<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conversations')) {
            return;
        }

        DB::table('conversations')
            ->where('channel', 'wa_bro')
            ->update(['channel' => 'wa_web']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('conversations')) {
            return;
        }

        DB::table('conversations')
            ->where('channel', 'wa_web')
            ->where('instance_id', 0)
            ->update(['channel' => 'wa_bro']);
    }
};
