<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('memos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('title');
            $table->string('company_name');
            $table->string('brand_name')->nullable();
            $table->string('contact_name');
            $table->string('job_title')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->date('deadline')->nullable();
            $table->string('account_executive')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'deadline', 'created_at']);
            $table->fullText(
                ['title', 'company_name', 'brand_name', 'contact_name', 'job_title', 'account_executive', 'note'],
                'memos_search_fulltext'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memos');
    }
};
