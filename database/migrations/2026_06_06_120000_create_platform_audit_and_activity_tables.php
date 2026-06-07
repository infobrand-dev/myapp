<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('actor_type', 150)->nullable();
            $table->string('actor_id', 64)->nullable();
            $table->string('impersonator_type', 150)->nullable();
            $table->string('impersonator_id', 64)->nullable();
            $table->string('entity_type', 150);
            $table->string('entity_id', 64)->nullable();
            $table->string('action', 150);
            $table->json('changed_fields')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'entity_type', 'entity_id'], 'platform_audit_logs_subject_idx');
            $table->index(['tenant_id', 'action', 'occurred_at'], 'platform_audit_logs_action_idx');
        });

        Schema::create('platform_activity_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('source_module', 100);
            $table->string('event_type', 150);
            $table->string('subject_type', 150);
            $table->string('subject_id', 64)->nullable();
            $table->string('actor_type', 150)->nullable();
            $table->string('actor_id', 64)->nullable();
            $table->string('summary', 255);
            $table->json('payload')->nullable();
            $table->json('actions')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'subject_type', 'subject_id'], 'platform_activity_subject_idx');
            $table->index(['tenant_id', 'event_type', 'occurred_at'], 'platform_activity_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_activity_events');
        Schema::dropIfExists('platform_audit_logs');
    }
};
