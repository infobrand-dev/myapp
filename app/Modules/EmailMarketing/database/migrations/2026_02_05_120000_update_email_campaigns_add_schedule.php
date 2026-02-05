<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->enum('status', ['draft', 'scheduled', 'running'])->default('draft')->change();
            $table->timestamp('scheduled_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->enum('status', ['draft', 'running'])->default('draft')->change();
            $table->dropColumn('scheduled_at');
        });
    }
};
