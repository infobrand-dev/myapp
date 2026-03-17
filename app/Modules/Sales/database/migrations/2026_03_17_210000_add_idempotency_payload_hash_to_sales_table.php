<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('idempotency_payload_hash', 64)->nullable()->after('external_reference');
            $table->index('idempotency_payload_hash');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['idempotency_payload_hash']);
            $table->dropColumn('idempotency_payload_hash');
        });
    }
};
