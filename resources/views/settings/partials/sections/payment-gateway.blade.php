<div class="row g-3">
    <div class="col-12">
        <form method="POST" action="{{ route('settings.payment-gateway.save') }}">
            @csrf
            @method('PUT')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment Gateway</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Provider Aktif</label>
                            <select name="provider" class="form-select">
                                <option value="manual" @selected(old('provider', optional($activePaymentGateway)->provider ?: 'manual') === 'manual')>Manual / Bayar Nanti</option>
                                @foreach($paymentGatewayProviders as $provider)
                                    <option value="{{ $provider['provider'] }}" @selected(old('provider', optional($activePaymentGateway)->provider) === $provider['provider'])>
                                        {{ $provider['label'] }}{{ $provider['configured'] ? '' : ' (belum dikonfigurasi)' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-hint">Provider aktif dipakai oleh checkout publik storefront untuk company aktif saat ini.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Saat Ini</label>
                            <div class="mt-2">
                                @if($activePaymentGatewayLabel)
                                    <span class="badge bg-green-lt text-green">{{ $activePaymentGatewayLabel }}</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary">Manual / Bayar Nanti</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Simpan Provider Aktif</button>
                </div>
            </div>
        </form>
    </div>

    <div class="col-12">
        <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Konfigurasi Provider</h3>
                </div>
                <div class="card-body">
                <div class="row g-3">
                    @foreach(($paymentGatewayProviders ?? []) as $provider)
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">{{ $provider['label'] }}</div>
                                <div class="text-muted small mb-3">
                                    Atur credential tenant, webhook, dan review transaksi provider ini.
                                </div>
                                <div class="small mb-3">
                                    <div class="mb-1">
                                        <span class="badge {{ $provider['ready'] ? 'bg-green-lt text-green' : 'bg-orange-lt text-orange' }}">
                                            {{ $provider['ready'] ? 'Siap Dipakai' : 'Perlu Konfigurasi' }}
                                        </span>
                                    </div>
                                    @if(!empty($provider['required_config_fields']))
                                        <div class="text-muted">Field wajib: {{ implode(', ', $provider['required_config_fields']) }}</div>
                                    @endif
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    @if(!empty($provider['settings_route']) && Route::has($provider['settings_route']))
                                        <a href="{{ route($provider['settings_route']) }}" class="btn btn-outline-primary btn-sm">Settings</a>
                                    @endif
                                    @if(!empty($provider['transactions_route']) && Route::has($provider['transactions_route']))
                                        <a href="{{ route($provider['transactions_route']) }}" class="btn btn-outline-secondary btn-sm">Transaksi</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
