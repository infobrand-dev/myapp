<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE email_campaign_recipients MODIFY delivery_status ENUM('pending','outgoing','delivered','bounced') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE email_campaign_recipients MODIFY delivery_status ENUM('pending','delivered','bounced') DEFAULT 'pending'");
    }
};
