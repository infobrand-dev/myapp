<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_tax_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('replaces_tax_document_id')->nullable()->after('document_status')->index();
            $table->text('status_reason')->nullable()->after('reference_note');
            $table->timestamp('issued_at')->nullable()->after('status_reason');
            $table->timestamp('replaced_at')->nullable()->after('issued_at');
            $table->timestamp('cancelled_at')->nullable()->after('replaced_at');
        });
    }

    public function down(): void
    {
        Schema::table('finance_tax_documents', function (Blueprint $table) {
            $table->dropColumn([
                'replaces_tax_document_id',
                'status_reason',
                'issued_at',
                'replaced_at',
                'cancelled_at',
            ]);
        });
    }
};
