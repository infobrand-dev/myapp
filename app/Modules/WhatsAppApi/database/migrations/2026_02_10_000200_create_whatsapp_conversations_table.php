<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Conversations tables are owned by Conversations module.
        // Keep this migration as compatibility shim for older deployments.
        if (!Schema::hasTable('conversations')) {
            return;
        }
    }

    public function down(): void
    {
        // Do not drop core conversations table from this module.
    }
};
