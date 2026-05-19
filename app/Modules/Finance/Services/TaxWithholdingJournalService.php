<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\FinanceTaxDocument;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use App\Support\AccountingJournalService;
use Illuminate\Support\Carbon;

class TaxWithholdingJournalService
{
    private $journalService;

    public function __construct(AccountingJournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function sync(FinanceTaxDocument $taxDocument): ?\App\Models\AccountingJournal
    {
        if ($taxDocument->document_type !== FinanceTaxDocument::TYPE_WITHHOLDING) {
            return null;
        }

        $amount = $this->withholdingAmount($taxDocument);
        if ($amount <= 0) {
            return null;
        }

        if ($taxDocument->document_status === FinanceTaxDocument::STATUS_DRAFT) {
            return null;
        }

        if ($taxDocument->document_status === FinanceTaxDocument::STATUS_CANCELLED && !$taxDocument->issued_at && !$taxDocument->replaced_at) {
            return null;
        }

        $lines = $this->journalLines($taxDocument, $amount);
        if (empty($lines)) {
            return null;
        }

        return $this->journalService->sync(
            $taxDocument,
            'tax_withholding',
            $taxDocument->document_date ? Carbon::parse($taxDocument->document_date) : now(),
            $lines,
            [
                'document_status' => $taxDocument->document_status,
                'document_number' => $taxDocument->document_number,
                'source_document_type' => $taxDocument->source_document_type,
                'source_document_id' => $taxDocument->source_document_id,
                'withheld_amount' => $amount,
            ],
            'Auto journal withholding tax ' . ($taxDocument->document_number ?: ('#' . $taxDocument->id))
        );
    }

    private function withholdingAmount(FinanceTaxDocument $taxDocument): float
    {
        $withheldAmount = round((float) $taxDocument->withheld_amount, 2);

        if ($withheldAmount > 0) {
            return $withheldAmount;
        }

        return round((float) $taxDocument->tax_amount, 2);
    }

    private function journalLines(FinanceTaxDocument $taxDocument, float $amount): array
    {
        $normalLines = $this->normalJournalLines($taxDocument, $amount);

        if ($taxDocument->document_status !== FinanceTaxDocument::STATUS_CANCELLED) {
            return $normalLines;
        }

        return array_map(function (array $line) {
            return [
                'account_code' => $line['account_code'],
                'account_name' => $line['account_name'],
                'debit' => $line['credit'],
                'credit' => $line['debit'],
                'meta' => ['withholding_cancellation' => true],
            ];
        }, $normalLines);
    }

    private function normalJournalLines(FinanceTaxDocument $taxDocument, float $amount): array
    {
        if ($taxDocument->source_document_type === Purchase::class) {
            return [
                [
                    'account_code' => 'AP',
                    'account_name' => 'Accounts Payable',
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_code' => $this->withholdingAccountCode($taxDocument, 'PPH_PAYABLE'),
                    'account_name' => $this->accountName($taxDocument, $this->withholdingAccountCode($taxDocument, 'PPH_PAYABLE'), 'PPh Withholding Payable'),
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ];
        }

        if ($taxDocument->source_document_type === Sale::class) {
            return [
                [
                    'account_code' => $this->withholdingAccountCode($taxDocument, 'PPH_RECEIVABLE'),
                    'account_name' => $this->accountName($taxDocument, $this->withholdingAccountCode($taxDocument, 'PPH_RECEIVABLE'), 'PPh Withholding Receivable'),
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_code' => 'AR',
                    'account_name' => 'Accounts Receivable',
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ];
        }

        return [
            [
                'account_code' => $this->withholdingAccountCode($taxDocument, 'PPH_PAYABLE'),
                'account_name' => $this->accountName($taxDocument, $this->withholdingAccountCode($taxDocument, 'PPH_PAYABLE'), 'PPh Withholding Payable'),
                'debit' => 0,
                'credit' => $amount,
            ],
            [
                'account_code' => 'TAX_ADJUSTMENT',
                'account_name' => 'Tax Adjustment Clearing',
                'debit' => $amount,
                'credit' => 0,
            ],
        ];
    }

    private function withholdingAccountCode(FinanceTaxDocument $taxDocument, string $fallback): string
    {
        $taxRate = $taxDocument->relationLoaded('taxRate') ? $taxDocument->taxRate : $taxDocument->taxRate()->first();

        return $taxRate && $taxRate->withholding_account_code
            ? (string) $taxRate->withholding_account_code
            : $fallback;
    }

    private function accountName(FinanceTaxDocument $taxDocument, string $accountCode, string $fallback): string
    {
        $account = ChartOfAccount::query()
            ->where('tenant_id', $taxDocument->tenant_id)
            ->where('company_id', $taxDocument->company_id)
            ->where('code', $accountCode)
            ->first();

        return $account ? (string) $account->name : $fallback;
    }
}
