<?php

namespace App\Modules\Finance\Http\Requests\Concerns;

use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesManualJournalPayload
{
    protected function manualJournalRules(): array
    {
        return [
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'posted'])],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()))],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_code' => ['required', 'string', 'max:50'],
            'lines.*.account_name' => ['required', 'string', 'max:120'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function normalizeManualJournalPayload(): void
    {
        $lines = collect($this->input('lines', []))
            ->filter(fn ($line) => is_array($line))
            ->map(function (array $line) {
                return [
                    'account_code' => trim((string) ($line['account_code'] ?? '')),
                    'account_name' => trim((string) ($line['account_name'] ?? '')),
                    'debit' => ($line['debit'] ?? '') === '' ? 0 : $line['debit'],
                    'credit' => ($line['credit'] ?? '') === '' ? 0 : $line['credit'],
                    'notes' => trim((string) ($line['notes'] ?? '')) ?: null,
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'status' => strtolower((string) ($this->input('status') ?: 'draft')),
            'description' => trim((string) $this->input('description', '')),
            'lines' => $lines,
        ]);
    }

    protected function validateManualJournalBalanced(Validator $validator): void
    {
        $lines = collect($this->input('lines', []));

        if ($lines->isEmpty()) {
            return;
        }

        $debit = round((float) $lines->sum(fn (array $line) => (float) ($line['debit'] ?? 0)), 2);
        $credit = round((float) $lines->sum(fn (array $line) => (float) ($line['credit'] ?? 0)), 2);

        foreach ($lines as $index => $line) {
            $lineDebit = round((float) ($line['debit'] ?? 0), 2);
            $lineCredit = round((float) ($line['credit'] ?? 0), 2);

            if ($lineDebit > 0 && $lineCredit > 0) {
                $validator->errors()->add("lines.{$index}.debit", 'Satu line journal tidak boleh memiliki debit dan credit sekaligus.');
            }

            if ($lineDebit <= 0 && $lineCredit <= 0) {
                $validator->errors()->add("lines.{$index}.debit", 'Setiap line journal harus memiliki debit atau credit.');
            }
        }

        if ($debit !== $credit) {
            $validator->errors()->add('lines', 'Total debit dan credit journal harus seimbang.');
        }
    }
}
