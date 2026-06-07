<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_event_outbox', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('event_name', 150);
            $table->unsignedSmallInteger('event_version')->default(1);
            $table->string('idempotency_key', 190)->unique();
            $table->string('subject_type', 150);
            $table->string('subject_id', 64)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'occurred_at'], 'platform_event_outbox_status_idx');
        });

        Schema::create('platform_webhook_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('provider', 100);
            $table->string('endpoint', 150);
            $table->boolean('signature_valid')->nullable();
            $table->string('dedupe_key', 190);
            $table->string('status', 30)->default('received');
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'endpoint', 'dedupe_key'], 'platform_webhook_receipts_dedupe_unique');
            $table->index(['provider', 'status', 'created_at'], 'platform_webhook_receipts_status_idx');
        });

        Schema::create('search_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('document_type', 100);
            $table->string('document_id', 64);
            $table->string('title', 255);
            $table->string('subtitle', 255)->nullable();
            $table->text('snippet')->nullable();
            $table->string('url', 500)->nullable();
            $table->text('search_vector')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->unique(['document_type', 'document_id'], 'search_documents_unique');
            $table->index(['tenant_id', 'document_type'], 'search_documents_type_idx');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX IF NOT EXISTS search_documents_title_trgm_idx ON search_documents USING GIN (title gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('search_documents');
        Schema::dropIfExists('platform_webhook_receipts');
        Schema::dropIfExists('platform_event_outbox');
    }
};
