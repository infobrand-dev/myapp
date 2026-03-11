<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conversation_messages')) {
            return;
        }

        Schema::table('conversation_messages', function (Blueprint $table) {
            $table->text('error_message')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conversation_messages')) {
            return;
        }

        Schema::table('conversation_messages', function (Blueprint $table) {
            $table->string('error_message')->nullable()->change();
        });
    }
};
