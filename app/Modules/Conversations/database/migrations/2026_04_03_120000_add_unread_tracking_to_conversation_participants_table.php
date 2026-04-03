<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->unsignedInteger('unread_count')->default(0)->after('role');
            $table->timestamp('last_read_at')->nullable()->after('invited_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropColumn(['unread_count', 'last_read_at']);
        });
    }
};
