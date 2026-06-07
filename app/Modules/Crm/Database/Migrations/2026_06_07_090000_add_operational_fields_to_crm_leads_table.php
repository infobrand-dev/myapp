<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->string('qualification_status')->nullable()->after('lead_source');
            $table->unsignedSmallInteger('lead_score')->nullable()->after('qualification_status');
            $table->date('expected_close_date')->nullable()->after('probability');

            $table->index(['tenant_id', 'qualification_status']);
            $table->index(['tenant_id', 'expected_close_date']);
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'qualification_status']);
            $table->dropIndex(['tenant_id', 'expected_close_date']);
            $table->dropColumn([
                'qualification_status',
                'lead_score',
                'expected_close_date',
            ]);
        });
    }
};
