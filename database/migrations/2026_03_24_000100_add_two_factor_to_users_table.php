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
            // Encrypted TOTP secret — null means 2FA is disabled for this user
            $secret = $table->text('two_factor_secret')->nullable();
            // Encrypted JSON array of single-use recovery codes
            $recoveryCodes = $table->text('two_factor_recovery_codes')->nullable();

            if ($supportsAfter) {
                $secret->after('password');
                $recoveryCodes->after('two_factor_secret');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes']);
        });
    }
};
