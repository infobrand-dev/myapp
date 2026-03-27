<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $supportsAfter = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::table('users', function (Blueprint $table) use ($supportsAfter) {
            $column = $table->string('locale', 5)->nullable();

            if ($supportsAfter) {
                $column->after('avatar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
