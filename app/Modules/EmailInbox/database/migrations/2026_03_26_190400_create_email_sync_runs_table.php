<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->foreignId('account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->string('sync_type', 32);
            $table->string('status', 32)->default('queued');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('stats_json')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'account_id', 'status']);
            $table->index(['tenant_id', 'sync_type', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sync_runs');
    }
};
