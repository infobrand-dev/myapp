<?php

use App\Models\DocumentSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_numbering_rules')) {
            Schema::create('document_numbering_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
                $table->string('scope_key', 40);
                $table->string('document_type', 40);
                $table->string('prefix', 30)->nullable();
                $table->string('number_format', 120)->nullable();
                $table->unsignedInteger('padding')->default(5);
                $table->unsignedBigInteger('next_number')->default(1);
                $table->string('last_period', 20)->nullable();
                $table->string('reset_period', 20)->default('never');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'company_id', 'scope_key', 'document_type'], 'document_numbering_rules_scope_document_unique');
                $table->index(['tenant_id', 'company_id', 'branch_id'], 'document_numbering_rules_scope_lookup_index');
                $table->index(['tenant_id', 'document_type'], 'document_numbering_rules_document_lookup_index');
            });
        }

        if (!Schema::hasTable('document_settings')) {
            return;
        }

        DocumentSetting::query()
            ->orderBy('id')
            ->get()
            ->each(function (DocumentSetting $setting): void {
                $customPrefix = filled($setting->invoice_prefix);
                $defaultPrefix = $customPrefix ? (string) $setting->invoice_prefix : 'SAL';
                $defaultFormat = $customPrefix ? '{PREFIX}-{SEQ}' : '{PREFIX}-{YYYYMMDD}-{SEQ}';

                DB::table('document_numbering_rules')->updateOrInsert(
                    [
                        'tenant_id' => $setting->tenant_id,
                        'company_id' => $setting->company_id,
                        'scope_key' => $setting->branch_id ? 'branch:'.$setting->branch_id : 'company',
                        'document_type' => 'sale',
                    ],
                    [
                        'branch_id' => $setting->branch_id,
                        'prefix' => $defaultPrefix,
                        'number_format' => $defaultFormat,
                        'padding' => max(1, (int) ($setting->invoice_padding ?: 5)),
                        'next_number' => max(1, (int) ($setting->invoice_next_number ?: 1)),
                        'last_period' => $setting->invoice_last_period,
                        'reset_period' => $setting->invoice_reset_period ?: 'never',
                        'notes' => 'Migrated from document_settings.invoice_* columns',
                        'created_at' => $setting->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_numbering_rules');
    }
};
