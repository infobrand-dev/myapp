<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('type');
            $table->foreignId('parent_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('name');
            $table->string('job_title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();
            $table->string('vat')->nullable();
            $table->string('company_registry')->nullable();
            $table->string('industry')->nullable();
            $table->string('street')->nullable();
            $table->string('street2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'company_id', 'branch_id', 'type', 'is_active']);
            $table->index(['tenant_id', 'is_active', 'created_at']);
            $table->index(['tenant_id', 'company_id', 'branch_id', 'created_at']);
            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'phone']);
            $table->index(['tenant_id', 'mobile']);

            if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
                $table->fullText(['name', 'notes'], 'contacts_search_fulltext');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
