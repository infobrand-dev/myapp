<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE email_campaigns MODIFY status ENUM('draft','scheduled','running','done') DEFAULT 'draft'");
        DB::statement("ALTER TABLE email_campaigns ADD COLUMN finished_at TIMESTAMP NULL AFTER scheduled_at");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE email_campaigns DROP COLUMN finished_at");
        DB::statement("ALTER TABLE email_campaigns MODIFY status ENUM('draft','scheduled','running') DEFAULT 'draft'");
    }
};
