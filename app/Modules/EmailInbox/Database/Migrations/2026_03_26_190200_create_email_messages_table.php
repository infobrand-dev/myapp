<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->foreignId('account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('email_folders')->nullOnDelete();
            $table->string('direction', 20);
            $table->string('status', 32)->default('draft');
            $table->string('message_id')->nullable();
            $table->string('in_reply_to')->nullable();
            $table->string('subject')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->json('to_json')->nullable();
            $table->json('cc_json')->nullable();
            $table->json('bcc_json')->nullable();
            $table->json('reply_to_json')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('has_attachments')->default(false);
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->json('raw_headers')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('sync_uid')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'account_id', 'received_at']);
            $table->index(['tenant_id', 'account_id', 'status']);
            $table->index(['tenant_id', 'folder_id', 'is_read']);
            $table->unique(['account_id', 'folder_id', 'sync_uid'], 'email_messages_account_folder_uid_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
