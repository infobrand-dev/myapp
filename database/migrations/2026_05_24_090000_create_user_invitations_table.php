<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_invitations')) {
            return;
        }

        Schema::create('user_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('role_name');
            $table->json('company_ids')->nullable();
            $table->json('branch_ids')->nullable();
            $table->unsignedBigInteger('default_company_id')->nullable();
            $table->unsignedBigInteger('default_branch_id')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'email', 'accepted_at', 'revoked_at'], 'user_invitations_tenant_email_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
    }
};
