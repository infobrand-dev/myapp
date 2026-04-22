<?php

namespace App\Support;

use App\Models\DocumentNumberingRule;
use App\Models\DocumentWorkflowRule;

class DocumentWorkflowService
{
    public function rulesForScope(int $tenantId, ?int $companyId, ?int $branchId = null): array
    {
        if (!$companyId) {
            return [
                'company' => collect(),
                'branch' => collect(),
            ];
        }

        $companyRules = DocumentWorkflowRule::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('scope_key', DocumentNumberingRule::scopeKeyFor())
            ->get()
            ->keyBy('document_type');

        $branchRules = $branchId
            ? DocumentWorkflowRule::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->where('scope_key', DocumentNumberingRule::scopeKeyFor($branchId))
                ->get()
                ->keyBy('document_type')
            : collect();

        return [
            'company' => $companyRules,
            'branch' => $branchRules,
        ];
    }

    public function resolveRuleForCurrentScope(string $documentType, ?int $companyId = null, ?int $branchId = null): ?DocumentWorkflowRule
    {
        $companyId = $companyId ?: CompanyContext::currentId();
        $branchId = func_num_args() >= 3 ? $branchId : BranchContext::currentOrDefaultId();

        if (!$companyId) {
            return null;
        }

        if ($branchId) {
            $branchRule = DocumentWorkflowRule::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->where('scope_key', DocumentNumberingRule::scopeKeyFor($branchId))
                ->where('document_type', $documentType)
                ->first();

            if ($branchRule) {
                return $branchRule;
            }
        }

        return DocumentWorkflowRule::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', $companyId)
            ->where('scope_key', DocumentNumberingRule::scopeKeyFor())
            ->where('document_type', $documentType)
            ->first();
    }

    public function requiresApprovalBeforeConversion(string $documentType, ?int $companyId = null, ?int $branchId = null): bool
    {
        $rule = $this->resolveRuleForCurrentScope($documentType, $companyId, $branchId);
        $definition = DocumentWorkflowRule::definition($documentType);

        return $rule
            ? (bool) $rule->requires_approval_before_conversion
            : (bool) ($definition['default_requires_approval_before_conversion'] ?? false);
    }

    public function requiresApprovalBeforeFinalize(string $documentType, ?int $companyId = null, ?int $branchId = null): bool
    {
        $rule = $this->resolveRuleForCurrentScope($documentType, $companyId, $branchId);
        $definition = DocumentWorkflowRule::definition($documentType);

        return $rule
            ? (bool) $rule->requires_approval_before_finalize
            : (bool) ($definition['default_requires_approval_before_finalize'] ?? false);
    }

    public function upsertRule(int $tenantId, int $companyId, ?int $branchId, string $documentType, array $attributes): DocumentWorkflowRule
    {
        $definition = DocumentWorkflowRule::definition($documentType);

        return DocumentWorkflowRule::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'scope_key' => DocumentNumberingRule::scopeKeyFor($branchId),
                'document_type' => $documentType,
            ],
            [
                'branch_id' => $branchId,
                'requires_approval_before_conversion' => (bool) ($attributes['requires_approval_before_conversion'] ?? $definition['default_requires_approval_before_conversion']),
                'requires_approval_before_finalize' => (bool) ($attributes['requires_approval_before_finalize'] ?? $definition['default_requires_approval_before_finalize']),
                'notes' => $attributes['notes'] ?? null,
            ]
        );
    }
}
