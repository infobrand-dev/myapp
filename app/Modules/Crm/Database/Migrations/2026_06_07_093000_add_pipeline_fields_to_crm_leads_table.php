<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->foreignId('pipeline_id')->nullable()->after('contact_id')->constrained('crm_pipelines')->nullOnDelete();
            $table->foreignId('stage_id')->nullable()->after('pipeline_id')->constrained('crm_pipeline_stages')->nullOnDelete();
            $table->string('visibility_scope', 30)->default('team')->after('position');
            $table->string('lost_reason', 255)->nullable()->after('lost_at');

            $table->index(['tenant_id', 'pipeline_id', 'stage_id']);
            $table->index(['tenant_id', 'visibility_scope', 'owner_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'pipeline_id', 'stage_id']);
            $table->dropIndex(['tenant_id', 'visibility_scope', 'owner_user_id']);
            $table->dropColumn([
                'pipeline_id',
                'stage_id',
                'visibility_scope',
                'lost_reason',
            ]);
        });
    }
};
