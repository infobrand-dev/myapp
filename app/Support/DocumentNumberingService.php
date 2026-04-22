<?php

namespace App\Support;

use App\Models\DocumentNumberingRule;

class DocumentNumberingService
{
    public function __construct()
    {
    }

    public function definitions(): array
    {
        return DocumentNumberingRule::definitions();
    }

    public function rulesForScope(int $tenantId, ?int $companyId, ?int $branchId = null): array
    {
        if (!$companyId) {
            return [
                'company' => collect(),
                'branch' => collect(),
            ];
        }

        $companyRules = DocumentNumberingRule::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('scope_key', DocumentNumberingRule::scopeKeyFor())
            ->get()
            ->keyBy('document_type');

        $branchRules = $branchId
            ? DocumentNumberingRule::query()
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

    public function nextConfiguredNumber(
        string $documentType,
        ?\DateTimeInterface $date = null,
        ?int $companyId = null,
        ?int $branchId = null,
        bool $lockForUpdate = true
    ): ?string {
        $date = $date ?: now();
        $companyId = $companyId ?? CompanyContext::currentId();
        $branchId = $branchId ?? BranchContext::currentOrDefaultId();

        if (!$companyId) {
            return null;
        }

        $rule = $this->resolveRule(TenantContext::currentId(), $companyId, $documentType, $branchId, $lockForUpdate);

        if (!$rule) {
            return null;
        }

        return $this->nextNumberFromRule($rule, $date);
    }

    public function previewNumber(
        string $documentType,
        ?DocumentNumberingRule $rule = null,
        ?\DateTimeInterface $date = null
    ): string {
        $definition = DocumentNumberingRule::definition($documentType);
        $date = $date ?: now();

        return $this->formatNumber(
            $rule ? ($rule->number_format ?: $definition['default_format']) : $definition['default_format'],
            $rule ? ($rule->prefix ?: $definition['default_prefix']) : $definition['default_prefix'],
            $date,
            max(1, (int) ($rule ? ($rule->padding ?: 5) : 5)),
            max(1, (int) ($rule ? ($rule->next_number ?: 1) : 1))
        );
    }

    public function upsertRule(
        int $tenantId,
        int $companyId,
        ?int $branchId,
        string $documentType,
        array $attributes
    ): DocumentNumberingRule {
        $definition = DocumentNumberingRule::definition($documentType);

        return DocumentNumberingRule::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'scope_key' => DocumentNumberingRule::scopeKeyFor($branchId),
                'document_type' => $documentType,
            ],
            [
                'branch_id' => $branchId,
                'prefix' => $attributes['prefix'] ?: $definition['default_prefix'],
                'number_format' => $attributes['number_format'] ?: $definition['default_format'],
                'padding' => max(1, (int) ($attributes['padding'] ?? 5)),
                'next_number' => max(1, (int) ($attributes['next_number'] ?? 1)),
                'last_period' => $attributes['last_period'] ?? null,
                'reset_period' => $attributes['reset_period'] ?: DocumentNumberingRule::RESET_NEVER,
                'notes' => $attributes['notes'] ?? null,
            ]
        );
    }

    private function resolveRule(
        int $tenantId,
        int $companyId,
        string $documentType,
        ?int $branchId = null,
        bool $lockForUpdate = true
    ): ?DocumentNumberingRule {
        if ($branchId) {
            $branchQuery = DocumentNumberingRule::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->where('scope_key', DocumentNumberingRule::scopeKeyFor($branchId))
                ->where('document_type', $documentType);

            if ($lockForUpdate) {
                $branchQuery->lockForUpdate();
            }

            $branchRule = $branchQuery->first();

            if ($branchRule) {
                return $branchRule;
            }
        }

        $companyQuery = DocumentNumberingRule::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('scope_key', DocumentNumberingRule::scopeKeyFor())
            ->where('document_type', $documentType);

        if ($lockForUpdate) {
            $companyQuery->lockForUpdate();
        }

        return $companyQuery->first();
    }

    private function nextNumberFromRule(DocumentNumberingRule $rule, \DateTimeInterface $date): string
    {
        $resetPeriod = $rule->reset_period ?: DocumentNumberingRule::RESET_NEVER;
        $currentPeriod = null;

        if ($resetPeriod === DocumentNumberingRule::RESET_MONTHLY) {
            $currentPeriod = $date->format('Y-m');
        } elseif ($resetPeriod === DocumentNumberingRule::RESET_YEARLY) {
            $currentPeriod = $date->format('Y');
        }

        $nextNumber = max(1, (int) ($rule->next_number ?: 1));

        if ($currentPeriod !== null && $rule->last_period !== $currentPeriod) {
            $nextNumber = 1;
        }

        $renderedNumber = $this->formatNumber(
            $rule->number_format ?: '{PREFIX}-{YYYYMMDD}-{SEQ}',
            $rule->prefix ?: DocumentNumberingRule::definition($rule->document_type)['default_prefix'],
            $date,
            max(1, (int) ($rule->padding ?: 5)),
            $nextNumber
        );

        $rule->forceFill([
            'next_number' => $nextNumber + 1,
            'last_period' => $currentPeriod,
        ])->save();

        return $renderedNumber;
    }

    private function formatNumber(
        string $numberFormat,
        string $prefix,
        \DateTimeInterface $date,
        int $padding,
        int $sequence
    ): string {
        $replacements = [
            '{PREFIX}' => $prefix,
            '{YYYY}' => $date->format('Y'),
            '{YY}' => $date->format('y'),
            '{MM}' => $date->format('m'),
            '{DD}' => $date->format('d'),
            '{YYYYMM}' => $date->format('Ym'),
            '{YYYYMMDD}' => $date->format('Ymd'),
            '{SEQ}' => str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT),
        ];

        return strtr($numberFormat, $replacements);
    }
}
