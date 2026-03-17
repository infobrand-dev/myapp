<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Models\Sale;

class SaleNumberService
{
    public function generate(?\DateTimeInterface $date = null): string
    {
        $date = $date ?: now();
        $prefix = 'SAL-' . $date->format('Ymd');
        $latest = Sale::query()
            ->where('sale_number', 'like', $prefix . '-%')
            ->orderByDesc('sale_number')
            ->value('sale_number');

        $nextSequence = 1;
        if ($latest && preg_match('/-(\d{4,})$/', $latest, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = sprintf('%s-%04d', $prefix, $nextSequence);
            $exists = Sale::query()->where('sale_number', $candidate)->exists();
            $nextSequence++;
        } while ($exists);

        return $candidate;
    }
}
