<?php

namespace App\Modules\Discounts\Actions;

use App\Modules\Discounts\Models\DiscountUsage;
use App\Modules\Discounts\Support\Engine\DiscountEvaluationResult;
use Illuminate\Support\Facades\DB;

class RecordDiscountUsageAction
{
    public function execute(array $payload, DiscountEvaluationResult $result): array
    {
        return DB::transaction(function () use ($payload, $result) {
            $records = [];
            $runningGrandTotal = (float) $result->subtotal;
            $runningLineTotals = [];

            foreach ($result->lineTotals as $line) {
                if (!is_array($line) || empty($line['line_key'])) {
                    continue;
                }

                $runningLineTotals[$line['line_key']] = (float) ($line['subtotal_before'] ?? 0);
            }

            foreach ($result->appliedDiscounts as $applied) {
                $runningGrandTotal = max(0, round($runningGrandTotal - (float) $applied['discount_amount'], 2));

                $usage = DiscountUsage::query()->create([
                    'discount_id' => $applied['discount_id'],
                    'voucher_id' => $applied['voucher_id'] ?? null,
                    'usage_reference_type' => $payload['usage_reference_type'] ?? null,
                    'usage_reference_id' => $payload['usage_reference_id'] ?? null,
                    'customer_reference_type' => data_get($payload, 'customer.reference_type', $payload['customer_reference_type'] ?? null),
                    'customer_reference_id' => data_get($payload, 'customer.reference_id', $payload['customer_reference_id'] ?? null),
                    'outlet_reference' => $payload['outlet_reference'] ?? null,
                    'sales_channel' => $payload['sales_channel'] ?? null,
                    'usage_status' => $payload['usage_status'] ?? 'applied',
                    'currency_code' => $payload['currency_code'] ?? 'IDR',
                    'subtotal_before' => $result->subtotal,
                    'discount_total' => $applied['discount_amount'],
                    'grand_total_after' => $result->grandTotal,
                    'evaluated_at' => now(),
                    'applied_at' => now(),
                    'snapshot' => [
                        'result' => $result->toArray(),
                        'applied_discount' => $applied,
                        'running_total_after_discount' => $runningGrandTotal,
                    ],
                    'meta' => array_merge($payload['meta'] ?? [], [
                        'running_total_after_discount' => $runningGrandTotal,
                    ]),
                ]);

                foreach ($result->lineTotals as $line) {
                    if (!is_array($line) || empty($line['line_key'])) {
                        continue;
                    }

                    $lineKey = $line['line_key'];
                    $lineAmount = (float) ($applied['line_discounts'][$lineKey] ?? 0);
                    if ($lineAmount <= 0) {
                        continue;
                    }

                    $lineRunningTotal = $runningLineTotals[$lineKey] ?? (float) ($line['subtotal_before'] ?? 0);
                    $lineRunningTotal = max(0, round($lineRunningTotal - $lineAmount, 2));
                    $runningLineTotals[$lineKey] = $lineRunningTotal;

                    $usage->lines()->create([
                        'discount_id' => $applied['discount_id'],
                        'voucher_id' => $applied['voucher_id'] ?? null,
                        'line_key' => $lineKey,
                        'product_id' => $line['product_id'] ?? null,
                        'variant_id' => $line['variant_id'] ?? null,
                        'quantity' => $line['quantity'] ?? 0,
                        'subtotal_before' => $line['subtotal_before'] ?? 0,
                        'discount_amount' => $lineAmount,
                        'total_after' => $lineRunningTotal,
                        'snapshot' => array_merge($line, [
                            'running_total_after_discount' => $lineRunningTotal,
                        ]),
                    ]);
                }

                $records[] = $usage->load('lines');
            }

            return $records;
        });
    }
}
