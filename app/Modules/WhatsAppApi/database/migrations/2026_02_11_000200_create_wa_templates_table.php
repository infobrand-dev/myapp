<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('name');
            $table->string('meta_name')->nullable();
            $table->string('language')->default('en');
            $table->string('category')->nullable(); // marketing/utility/authentication
            $table->string('namespace')->nullable();
            $table->string('meta_template_id')->nullable();
            $table->text('body');
            $table->json('components')->nullable(); // header/footer/buttons
            $table->json('variable_mappings')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_submitted_at')->nullable();
            $table->text('last_submit_error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'name']);
            $table->index(['tenant_id', 'namespace', 'status']);
            $table->fullText(['name', 'meta_name', 'body'], 'wa_templates_search_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_templates');
    }
};
