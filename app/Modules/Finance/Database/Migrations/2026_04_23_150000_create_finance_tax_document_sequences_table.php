<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_tax_document_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('document_type', 50);
            $table->string('sequence_date', 8);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'company_id', 'branch_id', 'document_type', 'sequence_date'],
                'finance_tax_document_sequences_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_tax_document_sequences');
    }
};
