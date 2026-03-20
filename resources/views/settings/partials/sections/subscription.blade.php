<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Current Subscription</h3>
            </div>
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Plan</div>
                <div class="fs-2 fw-bold mt-2">{{ optional($plan)->name ?? 'No active subscription' }}</div>
                <div class="text-muted small mt-1">{{ optional($plan)->code ?? 'billing-ready foundation only' }}</div>
                <div class="row g-3 mt-2">
                    <div class="col-sm-6">
                        <div class="text-secondary small text-uppercase fw-bold">Status</div>
                        <div class="fw-semibold mt-1">{{ optional($subscription)->status ?? 'inactive' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-secondary small text-uppercase fw-bold">Billing interval</div>
                        <div class="fw-semibold mt-1">{{ optional($plan)->billing_interval ?: '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-secondary small text-uppercase fw-bold">Starts at</div>
                        <div class="fw-semibold mt-1">{{ optional(optional($subscription)->starts_at)->format('d M Y') ?: '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-secondary small text-uppercase fw-bold">Ends at</div>
                        <div class="fw-semibold mt-1">{{ optional(optional($subscription)->ends_at)->format('d M Y') ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Quota Usage</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Resource</th>
                            <th>Usage</th>
                            <th>Limit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($limitSummaries as $limit)
                            <tr>
                                <td>{{ $limit['label'] }}</td>
                                <td>{{ $limit['usage'] }}</td>
                                <td>{{ $limit['limit'] ?? 'Unlimited' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">Feature Flags</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @forelse($availableFeatures as $feature)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div class="fw-semibold">{{ \Illuminate\Support\Str::headline($feature['key']) }}</div>
                            <span class="badge bg-{{ $feature['enabled'] ? 'success' : 'secondary' }}-lt text-{{ $feature['enabled'] ? 'success' : 'secondary' }}">
                                {{ $feature['enabled'] ? 'Enabled' : 'Disabled' }}
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
