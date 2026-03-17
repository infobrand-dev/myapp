<?php

namespace App\Modules\Discounts\Actions;

use App\Models\User;
use App\Modules\Discounts\Models\Discount;
use Illuminate\Support\Facades\DB;

class UpsertDiscountAction
{
    public function execute(array $data, ?Discount $discount = null, ?User $actor = null): Discount
    {
        return DB::transaction(function () use ($data, $discount, $actor) {
            $discount ??= new Discount();

            $discount->fill([
                'internal_name' => trim((string) $data['internal_name']),
                'public_label' => $this->nullableString($data['public_label'] ?? null),
                'code' => $this->nullableString($data['code'] ?? null),
                'description' => $this->nullableString($data['description'] ?? null),
                'discount_type' => $data['discount_type'],
                'application_scope' => $data['application_scope'],
                'currency_code' => $data['currency_code'] ?? 'IDR',
                'priority' => $data['priority'] ?? 100,
                'sequence' => $data['sequence'] ?? 100,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_archived' => (bool) ($data['is_archived'] ?? false),
                'is_voucher_required' => (bool) ($data['is_voucher_required'] ?? false),
                'is_manual_only' => (bool) ($data['is_manual_only'] ?? false),
                'is_override_allowed' => (bool) ($data['is_override_allowed'] ?? false),
                'stack_mode' => $data['stack_mode'] ?? 'stackable',
                'combination_mode' => $data['combination_mode'] ?? 'combinable',
                'usage_limit' => $data['usage_limit'] ?? null,
                'usage_limit_per_customer' => $data['usage_limit_per_customer'] ?? null,
                'max_discount_amount' => $data['max_discount_amount'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'schedule_json' => $data['schedule_json'] ?? null,
                'rule_payload' => $data['rule_payload'] ?? [],
                'meta' => [
                    'boundary' => 'products_master_data_only',
                    'source_module' => 'discounts',
                ],
                'updated_by' => $actor?->id,
            ]);

            if (!$discount->exists) {
                $discount->created_by = $actor?->id;
            }

            if ($discount->is_archived && !$discount->archived_at) {
                $discount->archived_at = now();
            }

            if (!$discount->is_archived) {
                $discount->archived_at = null;
            }

            $discount->save();

            $discount->targets()->delete();
            foreach (array_values($data['targets'] ?? []) as $index => $target) {
                if (empty($target['target_type'])) {
                    continue;
                }

                $discount->targets()->create([
                    'target_type' => $target['target_type'],
                    'target_id' => $target['target_id'] ?? null,
                    'target_code' => $this->nullableString($target['target_code'] ?? null),
                    'operator' => $target['operator'] ?? 'include',
                    'sort_order' => $index,
                    'payload' => $target['payload'] ?? null,
                ]);
            }

            $discount->conditions()->delete();
            foreach (array_values($data['conditions'] ?? []) as $index => $condition) {
                if (empty($condition['condition_type'])) {
                    continue;
                }

                $discount->conditions()->create([
                    'condition_type' => $condition['condition_type'],
                    'operator' => $condition['operator'] ?? '>=',
                    'value_type' => $condition['value_type'] ?? 'string',
                    'value' => $this->nullableString($condition['value'] ?? null),
                    'secondary_value' => $condition['secondary_value'] ?? null,
                    'sort_order' => $index,
                    'payload' => $condition['payload'] ?? null,
                ]);
            }

            $existingVouchers = $discount->vouchers()->get()->keyBy('id');
            $keptVoucherIds = [];

            foreach (array_values($data['vouchers'] ?? []) as $voucher) {
                if (empty($voucher['code'])) {
                    continue;
                }

                $voucherModel = null;
                $voucherId = $voucher['id'] ?? null;
                if ($voucherId) {
                    $voucherModel = $existingVouchers->get((int) $voucherId);
                }

                if (!$voucherModel) {
                    $voucherModel = $discount->vouchers()
                        ->whereRaw('LOWER(code) = ?', [strtolower(trim((string) $voucher['code']))])
                        ->first();
                }

                if (!$voucherModel) {
                    $voucherModel = $discount->vouchers()->make();
                }

                $voucherModel->fill([
                    'code' => strtoupper(trim((string) $voucher['code'])),
                    'description' => $this->nullableString($voucher['description'] ?? null),
                    'starts_at' => $voucher['starts_at'] ?? null,
                    'ends_at' => $voucher['ends_at'] ?? null,
                    'usage_limit' => $voucher['usage_limit'] ?? null,
                    'usage_limit_per_customer' => $voucher['usage_limit_per_customer'] ?? null,
                    'is_active' => (bool) ($voucher['is_active'] ?? true),
                    'meta' => $voucher['meta'] ?? null,
                ]);
                $voucherModel->discount_id = $discount->id;
                $voucherModel->save();

                $keptVoucherIds[] = $voucherModel->id;
            }

            $staleVoucherQuery = $discount->vouchers();
            if (!empty($keptVoucherIds)) {
                $staleVoucherQuery->whereNotIn('id', $keptVoucherIds);
            }

            foreach ($staleVoucherQuery->get() as $staleVoucher) {
                $meta = $staleVoucher->meta ?? [];
                $meta['archived_by_discount_sync'] = true;

                $staleVoucher->forceFill([
                    'is_active' => false,
                    'meta' => $meta,
                ])->save();
            }

            return $discount->fresh(['targets', 'conditions', 'vouchers']);
        });
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
