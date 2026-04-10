@php
    $activities = $activities ?? collect();
    $fieldLabels = $fieldLabels ?? [];
    $money = $money ?? app(\App\Support\MoneyFormatter::class);
    $currency = $currency ?? app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp

@if(($accountingUiMode ?? 'standard') === 'advanced' && $activities->isNotEmpty())
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-history me-2 text-muted"></i>Change History
        </h3>
        <div class="card-options">
            <span class="badge bg-secondary-lt text-secondary">{{ $activities->count() }} event</span>
        </div>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            @foreach($activities as $activity)
            <li class="list-group-item">
                <div class="d-flex align-items-start gap-3">
                    @if($activity->event === 'created')
                        <span class="avatar avatar-sm bg-green-lt flex-shrink-0">
                            <i class="ti ti-plus" style="font-size:.9rem; color:var(--tblr-green);"></i>
                        </span>
                    @elseif($activity->event === 'deleted')
                        <span class="avatar avatar-sm bg-red-lt flex-shrink-0">
                            <i class="ti ti-trash" style="font-size:.9rem; color:var(--tblr-red);"></i>
                        </span>
                    @else
                        <span class="avatar avatar-sm bg-blue-lt flex-shrink-0">
                            <i class="ti ti-pencil" style="font-size:.9rem; color:var(--tblr-blue);"></i>
                        </span>
                    @endif

                    <div class="flex-fill">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <span class="fw-medium">{{ $activity->causer?->name ?? 'System' }}</span>
                                <span class="text-muted ms-1">
                                    {{ $activity->event === 'created' ? 'created this record' : ($activity->event === 'deleted' ? 'deleted this record' : 'updated this record') }}
                                </span>
                            </div>
                            <span class="text-muted small flex-shrink-0 ms-3">
                                {{ $activity->created_at->format('d M Y, H:i') }}
                            </span>
                        </div>

                        @if($activity->event === 'updated' && !empty($activity->properties['old']))
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach($activity->properties['old'] as $field => $oldValue)
                                    @php
                                        $newValue = $activity->properties['attributes'][$field] ?? null;
                                        $label = $fieldLabels[$field] ?? $field;
                                        $render = function ($value) use ($field, $money, $currency) {
                                            if ($value === null || $value === '') {
                                                return '(empty)';
                                            }

                                            if (in_array($field, ['amount', 'cost_price', 'sell_price', 'wholesale_price', 'member_price', 'subtotal', 'discount_total', 'tax_total', 'grand_total', 'paid_total', 'balance_due'], true)) {
                                                return $money->format((float) $value, $currency);
                                            }

                                            if (str_contains($field, 'date') || str_ends_with($field, '_at')) {
                                                try {
                                                    return \Carbon\Carbon::parse($value)->format('d M Y, H:i');
                                                } catch (\Throwable $e) {
                                                    return (string) $value;
                                                }
                                            }

                                            if (is_bool($value)) {
                                                return $value ? 'Yes' : 'No';
                                            }

                                            return (string) $value;
                                        };
                                    @endphp
                                    <div class="border rounded px-2 py-1 small bg-body-secondary">
                                        <span class="text-muted">{{ $label }}:</span>
                                        <span class="text-muted text-decoration-line-through ms-1">{{ $render($oldValue) }}</span>
                                        <i class="ti ti-arrow-right mx-1 text-muted" style="font-size:.75rem;"></i>
                                        <span class="fw-medium">{{ $render($newValue) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
    </div>
</div>
@endif
