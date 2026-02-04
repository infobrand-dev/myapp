<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subtasks', function (Blueprint $table) {
            $table->string('pic')->nullable()->after('title');
            $table->date('due_date')->nullable()->after('pic');
        });
    }

    public function down(): void
    {
        Schema::table('subtasks', function (Blueprint $table) {
            $table->dropColumn(['pic', 'due_date']);
        });
    }
};
