<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('stage')->default('new_lead');
            $table->string('priority')->default('medium');
            $table->string('lead_source')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->string('currency', 10)->default('IDR');
            $table->unsignedTinyInteger('probability')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('labels')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'stage', 'is_archived']);
            $table->index(['tenant_id', 'owner_user_id', 'stage']);
            $table->index(['tenant_id', 'company_id', 'branch_id']);
            $table->index(['tenant_id', 'contact_id']);
            $table->index(['tenant_id', 'next_follow_up_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};
