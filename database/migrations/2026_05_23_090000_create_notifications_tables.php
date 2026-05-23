<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('module', 100);
            $table->string('type', 150);
            $table->string('severity', 20)->default('info');
            $table->string('status', 20)->default('active');
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->string('resource_type', 150)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('dedupe_key', 255)->nullable();
            $table->json('actions')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_id', 'branch_id', 'status', 'severity'], 'notifications_scope_status_idx');
            $table->index(['tenant_id', 'module', 'type'], 'notifications_module_type_idx');
            $table->index(['tenant_id', 'dedupe_key', 'status'], 'notifications_dedupe_status_idx');
        });

        Schema::create('notification_recipients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_id', 'user_id'], 'notification_recipients_notification_user_unique');
            $table->index(['tenant_id', 'user_id', 'is_read'], 'notification_recipients_unread_idx');
            $table->index(['tenant_id', 'user_id', 'dismissed_at', 'archived_at'], 'notification_recipients_visibility_idx');
            $table->foreign('notification_id')->references('id')->on('notifications')->cascadeOnDelete();
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('notification_recipient_id')->nullable();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('channel', 30);
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'channel', 'status'], 'notification_deliveries_queue_idx');
            $table->index(['notification_id', 'user_id', 'channel'], 'notification_deliveries_lookup_idx');
            $table->foreign('notification_id')->references('id')->on('notifications')->cascadeOnDelete();
            $table->foreign('notification_recipient_id')->references('id')->on('notification_recipients')->nullOnDelete();
        });

        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('notification_type', 150);
            $table->string('channel', 30);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'user_id', 'notification_type', 'channel'], 'notification_preferences_unique');
        });

        Schema::create('notification_push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->text('endpoint');
            $table->string('public_key', 500);
            $table->string('auth_token', 255);
            $table->string('content_encoding', 50)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'endpoint'], 'notification_push_subscriptions_user_endpoint_unique');
            $table->index(['tenant_id', 'user_id', 'is_active'], 'notification_push_subscriptions_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_push_subscriptions');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_recipients');
        Schema::dropIfExists('notifications');
    }
};
