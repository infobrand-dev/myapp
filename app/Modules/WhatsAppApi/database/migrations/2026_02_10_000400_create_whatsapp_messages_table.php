<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // conversation_messages is owned by Conversations module.
    }

    public function down(): void
    {
        // No-op. Avoid dropping shared tables.
    }
};
