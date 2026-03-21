<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_companies')) {
            Schema::create('user_companies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->unique(['tenant_id', 'user_id', 'company_id']);
                $table->index(['tenant_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('user_branches')) {
            Schema::create('user_branches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->unique(['tenant_id', 'user_id', 'branch_id']);
                $table->index(['tenant_id', 'user_id', 'company_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branches');
        Schema::dropIfExists('user_companies');
    }
};
