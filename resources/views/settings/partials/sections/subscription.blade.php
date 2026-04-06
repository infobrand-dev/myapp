@php
    $storageFormatter = app(\App\Support\StorageSizeFormatter::class);
    $money = app(\App\Support\MoneyFormatter::class);
    $hasLimitAdvice = collect($limitSummaries)->contains(function ($limit) {
        return !empty($limit['advice']);
    });
@endphp
@if($billingInvoices->isNotEmpty())
    <div class="alert alert-danger mb-3">
        <div class="fw-semibold">Ada tagihan langganan yang masih perlu dibayar.</div>
        <div class="small mt-1">Segera buka bagian tagihan di bawah ini agar akses dan aktivasi layanan tidak tertunda.</div>
    </div>
@endif
<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Langganan Aktif</h3>
            </div>
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Plan</div>
                <div class="fs-2 fw-bold mt-2">{{ optional($plan)->display_name ?? optional($plan)->name ?? 'Belum ada langganan aktif' }}</div>
                <div class="text-muted small mt-1">Paket aktif untuk workspace Anda.</div>
                <div class="row g-3 mt-2">
                    <div class="col-sm-6">
                        <div class="text-secondary small text-uppercase fw-bold">Status</div>
                        <div class="fw-semibold mt-1">{{ optional($subscription)->status ?? 'nonaktif' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-secondary small text-uppercase fw-bold">Siklus Tagihan</div>
                        <div class="fw-semibold mt-1">{{ optional($plan)->billing_interval_label ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-secondary small text-uppercase fw-bold">Mulai</div>
                        <div class="fw-semibold mt-1">{{ optional(optional($subscription)->starts_at)->format('d M Y') ?: '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-secondary small text-uppercase fw-bold">Berakhir</div>
                        <div class="fw-semibold mt-1">{{ optional(optional($subscription)->ends_at)->format('d M Y') ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Kuota Penggunaan</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Sumber Daya</th>
                            <th>Penggunaan</th>
                            <th>Batas</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($limitSummaries as $limit)
                            @php
                                $statusMap = [
                                    'ok' => [
                                        'label' => $limit['limit'] === null ? 'Tidak terbatas' : 'OK',
                                        'class' => $limit['limit'] === null ? 'bg-azure-lt text-azure' : 'bg-success-lt text-success',
                                    ],
                                    'near_limit' => ['label' => 'Near limit', 'class' => 'bg-warning-lt text-warning'],
                                    'at_limit' => ['label' => 'At limit', 'class' => 'bg-danger-lt text-danger'],
                                    'over_limit' => ['label' => 'Over limit', 'class' => 'bg-danger-lt text-danger'],
                                ];
                                $statusInfo = $statusMap[$limit['status']] ?? $statusMap['ok'];
                                $isStorage = $limit['key'] === \App\Support\PlanLimit::TOTAL_STORAGE_BYTES;
                                $usageValue = $isStorage ? $storageFormatter->format((int) $limit['usage']) : (is_numeric($limit['usage']) ? number_format((int) $limit['usage']) : $limit['usage']);
                                $limitValue = $limit['limit'] === null
                                    ? 'Tidak terbatas'
                                    : ($isStorage ? $storageFormatter->format((int) $limit['limit']) : number_format((int) $limit['limit']));
                            @endphp
                            <tr>
                                <td>{{ $limit['label'] }}</td>
                                <td>{{ $usageValue }}</td>
                                <td>{{ $limitValue }}</td>
                                <td><span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span></td>
                                <td class="small text-muted">{{ $limit['advice']['tenant_cta'] ?? 'Tidak perlu tindakan saat ini.' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($hasLimitAdvice)
                <div class="card-body border-top">
                    <div class="alert alert-warning mb-0">
                        Jika kapasitas hampir habis atau sudah habis, tenant tetap bisa memakai data yang sudah ada, tetapi penambahan resource baru akan diblokir. Hubungi admin platform untuk upgrade plan, penyesuaian kapasitas, atau top up AI Credits.
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title mb-0">Tagihan Langganan</h3>
    </div>
    <div class="card-body">
        @if($billingInvoices->isEmpty())
            <div class="text-muted">Saat ini tidak ada tagihan langganan yang menunggu pembayaran.</div>
        @else
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Plan</th>
                            <th>Jatuh Tempo</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($billingInvoices as $billingItem)
                            @php
                                $invoice = $billingItem['invoice'];
                                $statusMap = [
                                    'issued' => ['label' => 'Menunggu Bayar', 'class' => 'bg-danger-lt text-danger'],
                                    'pending' => ['label' => 'Pending', 'class' => 'bg-warning-lt text-warning'],
                                    'paid' => ['label' => 'Paid', 'class' => 'bg-success-lt text-success'],
                                    'void' => ['label' => 'Void', 'class' => 'bg-secondary-lt text-secondary'],
                                ];
                                $statusInfo = $statusMap[$invoice->status] ?? ['label' => strtoupper((string) $invoice->status), 'class' => 'bg-secondary-lt text-secondary'];
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $invoice->invoice_number }}</div>
                                    <div class="text-muted small">{{ optional($invoice->issued_at)->format('d M Y H:i') ?: '-' }}</div>
                                </td>
                                <td>{{ optional($invoice->plan)->display_name ?? optional($invoice->plan)->name ?? '-' }}</td>
                                <td>{{ optional($invoice->due_at)->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $money->format((float) $invoice->amount, $invoice->currency) }}</td>
                                <td><span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span></td>
                                <td class="text-end">
                                    <a href="{{ $billingItem['public_url'] }}" class="btn btn-sm btn-primary">Lihat / Bayar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title mb-0">Top Up AI Credits</h3>
    </div>
    <div class="card-body">
        <div class="text-muted small mb-3">
            AI Credits dipakai saat Managed AI membantu memproses percakapan. Jika kuota hampir habis, hubungi admin platform untuk top up atau upgrade plan.
        </div>
        <div class="row g-3">
            @foreach($aiCreditPricing['packs'] as $pack)
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="fw-semibold">Top Up {{ number_format($pack['credits']) }} AI Credits</div>
                        <div class="text-muted small mt-1">{{ $money->format($pack['price'], $aiCreditPricing['currency']) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="text-muted small mt-3">{{ $money->format($aiCreditPricing['price_per_credit'], $aiCreditPricing['currency']) }} / AI Credit.</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">Fitur Plan</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @forelse($availableFeatures as $feature)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div class="fw-semibold">{{ \Illuminate\Support\Str::headline($feature['key']) }}</div>
                            <span class="badge bg-{{ $feature['enabled'] ? 'success' : 'secondary' }}-lt text-{{ $feature['enabled'] ? 'success' : 'secondary' }}">
                                {{ $feature['enabled'] ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-muted">Belum ada definisi feature flag pada plan tenant ini.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>
