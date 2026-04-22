<?php

namespace App\Support;

use App\Models\DocumentNumberingRule;
use App\Models\DocumentSetting;

class DocumentSettingsResolver
{
    private $documentNumbering;
    private $documentWorkflow;

    public function __construct(DocumentNumberingService $documentNumbering, DocumentWorkflowService $documentWorkflow)
    {
        $this->documentNumbering = $documentNumbering;
        $this->documentWorkflow = $documentWorkflow;
    }

    public function forScope(int $tenantId, ?int $companyId, ?int $branchId = null): array
    {
        if (!$companyId) {
            return $this->defaults();
        }

        $companySetting = DocumentSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereNull('branch_id')
            ->first();

        $branchSetting = $branchId
            ? DocumentSetting::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->first()
            : null;

        $numberingRules = $this->documentNumbering->rulesForScope($tenantId, $companyId, $branchId);
        $workflowRules = $this->documentWorkflow->rulesForScope($tenantId, $companyId, $branchId);

        return [
            'company' => $companySetting,
            'branch' => $branchSetting,
            'company_numbering_rules' => $numberingRules['company'],
            'branch_numbering_rules' => $numberingRules['branch'],
            'company_workflow_rules' => $workflowRules['company'],
            'branch_workflow_rules' => $workflowRules['branch'],
            'document_header' => $branchSetting->document_header ?? $companySetting->document_header ?? null,
            'document_footer' => $branchSetting->document_footer ?? $companySetting->document_footer ?? null,
            'receipt_footer' => $branchSetting->receipt_footer ?? $companySetting->receipt_footer ?? null,
        ];
    }

    private function defaults(): array
    {
        return [
            'company' => null,
            'branch' => null,
            'company_numbering_rules' => collect(),
            'branch_numbering_rules' => collect(),
            'company_workflow_rules' => collect(),
            'branch_workflow_rules' => collect(),
            'document_header' => null,
            'document_footer' => null,
            'receipt_footer' => null,
        ];
    }

    public function previewForSettingsPage(int $tenantId, ?int $companyId, ?int $branchId = null): array
    {
        $resolved = $this->forScope($tenantId, $companyId, $branchId);
        $companySetting = $resolved['company'];
        $branchSetting = $resolved['branch'];
        $branchSelected = $branchId !== null;
        $companyRules = $resolved['company_numbering_rules'];
        $branchRules = $resolved['branch_numbering_rules'];
        $companyWorkflowRules = $resolved['company_workflow_rules'];
        $branchWorkflowRules = $resolved['branch_workflow_rules'];
        $documentTypes = [];

        foreach (DocumentNumberingRule::definitions() as $documentType => $definition) {
            $companyRule = $companyRules->get($documentType);
            $branchRule = $branchRules->get($documentType);
            $effectiveRule = $branchRule ?: $companyRule;
            $companyWorkflowRule = $companyWorkflowRules->get($documentType);
            $branchWorkflowRule = $branchWorkflowRules->get($documentType);
            $effectiveWorkflowRule = $branchWorkflowRule ?: $companyWorkflowRule;
            $workflowDefinition = \App\Models\DocumentWorkflowRule::definition($documentType);

            $documentTypes[] = [
                'type' => $documentType,
                'label' => $definition['label'],
                'applies_to' => $definition['applies_to'],
                'company_preview' => $this->documentNumbering->previewNumber($documentType, $companyRule),
                'branch_preview' => $branchRule ? $this->documentNumbering->previewNumber($documentType, $branchRule) : null,
                'effective_preview' => $this->documentNumbering->previewNumber($documentType, $effectiveRule),
                'company_rule' => $companyRule,
                'branch_rule' => $branchRule,
                'effective_rule' => $effectiveRule,
                'effective_source' => $branchRule ? 'Branch override' : 'Company default',
                'effective_reset_period' => $effectiveRule ? ($effectiveRule->reset_period ?: DocumentNumberingRule::RESET_NEVER) : DocumentNumberingRule::RESET_NEVER,
                'company_workflow_rule' => $companyWorkflowRule,
                'branch_workflow_rule' => $branchWorkflowRule,
                'requires_approval_before_conversion' => $effectiveWorkflowRule
                    ? (bool) $effectiveWorkflowRule->requires_approval_before_conversion
                    : (bool) ($workflowDefinition['default_requires_approval_before_conversion'] ?? false),
                'requires_approval_before_finalize' => $effectiveWorkflowRule
                    ? (bool) $effectiveWorkflowRule->requires_approval_before_finalize
                    : (bool) ($workflowDefinition['default_requires_approval_before_finalize'] ?? false),
            ];
        }

        return [
            'numbering_documents' => $documentTypes,
            'effective_header' => $resolved['document_header'],
            'effective_footer' => $resolved['document_footer'],
            'effective_receipt_footer' => $resolved['receipt_footer'],
            'branch_selected' => $branchSelected,
            'rollout_summary' => 'Numbering sudah disatukan ke rule per dokumen dengan scope company lalu branch override.',
            'future_summary' => 'Dokumen baru tinggal menambah document_type baru tanpa ubah struktur tabel utama.',
        ];
    }
}
