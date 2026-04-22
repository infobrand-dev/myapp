<?php

use App\Models\DocumentNumberingRule;
use App\Models\DocumentWorkflowRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_workflow_rules')) {
            Schema::create('document_workflow_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
                $table->string('scope_key', 40);
                $table->string('document_type', 40);
                $table->boolean('requires_approval_before_conversion')->default(false);
                $table->boolean('requires_approval_before_finalize')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'company_id', 'scope_key', 'document_type'], 'document_workflow_rules_scope_document_unique');
                $table->index(['tenant_id', 'company_id', 'branch_id'], 'document_workflow_rules_scope_lookup_index');
            });
        }

        if (!Schema::hasTable('document_numbering_rules')) {
            return;
        }

        $definitions = DocumentWorkflowRule::definitions();

        DB::table('document_numbering_rules')
            ->select('tenant_id', 'company_id', 'branch_id', 'scope_key')
            ->distinct()
            ->orderBy('tenant_id')
            ->orderBy('company_id')
            ->orderBy('scope_key')
            ->get()
            ->each(function ($scope) use ($definitions): void {
                foreach ($definitions as $documentType => $definition) {
                    DB::table('document_workflow_rules')->updateOrInsert(
                        [
                            'tenant_id' => $scope->tenant_id,
                            'company_id' => $scope->company_id,
                            'scope_key' => $scope->scope_key,
                            'document_type' => $documentType,
                        ],
                        [
                            'branch_id' => $scope->branch_id,
                            'requires_approval_before_conversion' => (bool) ($definition['default_requires_approval_before_conversion'] ?? false),
                            'requires_approval_before_finalize' => (bool) ($definition['default_requires_approval_before_finalize'] ?? false),
                            'notes' => 'Seeded from document workflow definitions',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_workflow_rules');
    }
};
