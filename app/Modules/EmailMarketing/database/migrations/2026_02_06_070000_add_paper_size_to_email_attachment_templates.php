<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_attachment_templates', function (Blueprint $table) {
            $table->string('paper_size')->default('A4')->after('mime');
        });
    }

    public function down(): void
    {
        Schema::table('email_attachment_templates', function (Blueprint $table) {
            $table->dropColumn('paper_size');
        });
    }
};
