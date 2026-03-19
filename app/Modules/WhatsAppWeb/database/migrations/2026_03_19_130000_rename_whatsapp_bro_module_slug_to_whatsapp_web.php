<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('modules')) {
            return;
        }

        DB::table('modules')
            ->where('slug', 'whatsapp_bro')
            ->update([
                'slug' => 'whatsapp_web',
                'name' => 'WhatsApp Web',
                'provider' => 'App\\Modules\\WhatsAppWeb\\WhatsAppWebServiceProvider',
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('modules')) {
            return;
        }

        DB::table('modules')
            ->where('slug', 'whatsapp_web')
            ->update([
                'slug' => 'whatsapp_bro',
                'name' => 'WhatsApp Bro',
                'provider' => 'App\\Modules\\WhatsAppWeb\\WhatsAppWebServiceProvider',
            ]);
    }
};
