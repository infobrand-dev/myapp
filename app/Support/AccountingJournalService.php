<?php

namespace App\Support;

use App\Models\AccountingJournal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AccountingJournalService
{
    public function sync(Model $source, string $entryType, string|\DateTimeInterface $entryDate, array $lines, array $meta = [], ?string $description = null): AccountingJournal
    {
        $tenantId = TenantContext::currentId();
        $companyId = CompanyContext::currentId();
        $branchId = $source->branch_id ?? null;
        $resolvedDate = Carbon::parse($entryDate);

        $journal = AccountingJournal::query()->firstOrNew([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'entry_type' => $entryType,
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
        ]);

        $journal->fill([
            'branch_id' => $branchId,
            'journal_number' => $journal->journal_number ?: $this->journalNumber($entryType, $resolvedDate),
            'entry_date' => $resolvedDate,
            'status' => 'posted',
            'description' => $description,
            'meta' => $meta,
            'updated_by' => auth()->id(),
        ]);

        if (!$journal->exists) {
            $journal->created_by = auth()->id();
        }

        $journal->save();

        $normalizedLines = $this->normalizeLines($lines);

        $journal->lines()->delete();
        foreach ($normalizedLines as $index => $line) {
            $journal->lines()->create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'line_no' => $index + 1,
                'account_code' => $line['account_code'],
                'account_name' => $line['account_name'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'meta' => $line['meta'] ?? null,
            ]);
        }

        return $journal->load('lines');
    }

    private function normalizeLines(array $lines): array
    {
        $normalized = collect($lines)
            ->filter(fn ($line) => is_array($line))
            ->map(function (array $line) {
                return [
                    'account_code' => (string) ($line['account_code'] ?? ''),
                    'account_name' => (string) ($line['account_name'] ?? ''),
                    'debit' => round((float) ($line['debit'] ?? 0), 2),
                    'credit' => round((float) ($line['credit'] ?? 0), 2),
                    'meta' => $line['meta'] ?? null,
                ];
            })
            ->filter(fn (array $line) => $line['account_code'] !== '' && ($line['debit'] > 0 || $line['credit'] > 0))
            ->values();

        $debit = round((float) $normalized->sum('debit'), 2);
        $credit = round((float) $normalized->sum('credit'), 2);

        if ($debit !== $credit) {
            throw new \InvalidArgumentException('Accounting journal lines are not balanced.');
        }

        return $normalized->all();
    }

    private function journalNumber(string $entryType, Carbon $entryDate): string
    {
        return 'JRNL-' . strtoupper(Str::slug($entryType, '')) . '-' . $entryDate->format('YmdHis');
    }
}
