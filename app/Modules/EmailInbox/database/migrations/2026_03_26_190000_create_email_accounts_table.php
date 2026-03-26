<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->string('email_address');
            $table->string('provider', 60)->nullable();
            $table->string('direction_mode', 32)->default('inbound_outbound');
            $table->string('inbound_protocol', 20)->default('imap');
            $table->string('inbound_host')->nullable();
            $table->unsignedSmallInteger('inbound_port')->nullable();
            $table->string('inbound_encryption', 20)->nullable();
            $table->string('inbound_username')->nullable();
            $table->text('inbound_password')->nullable();
            $table->boolean('inbound_validate_cert')->default(true);
            $table->string('outbound_host')->nullable();
            $table->unsignedSmallInteger('outbound_port')->nullable();
            $table->string('outbound_encryption', 20)->nullable();
            $table->string('outbound_username')->nullable();
            $table->text('outbound_password')->nullable();
            $table->string('outbound_from_name')->nullable();
            $table->string('outbound_reply_to')->nullable();
            $table->boolean('sync_enabled')->default(true);
            $table->string('sync_status', 32)->default('idle');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'email_address']);
            $table->index(['tenant_id', 'company_id', 'branch_id']);
            $table->index(['tenant_id', 'sync_enabled', 'sync_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
