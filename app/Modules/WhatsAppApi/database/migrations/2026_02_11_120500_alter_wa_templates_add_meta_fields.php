<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_templates', function (Blueprint $table) {
            $table->string('meta_template_id')->nullable()->after('namespace');
            $table->timestamp('last_submitted_at')->nullable()->after('status');
            $table->text('last_submit_error')->nullable()->after('last_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('wa_templates', function (Blueprint $table) {
            $table->dropColumn(['meta_template_id', 'last_submitted_at', 'last_submit_error']);
        });
    }
};
