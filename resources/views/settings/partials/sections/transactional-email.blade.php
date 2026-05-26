@php
    $setting = $transactionalMailSetting ?? new \App\Models\TenantTransactionalMailSetting();
    $capabilities = $transactionalMailCapabilities ?? ['managed' => false, 'custom_smtp' => false];
    $selectedMode = old('delivery_mode', $setting->deliveryMode());
    $managedQuota = $transactionalMailManagedQuota ?? null;
@endphp

<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('settings.transactional-email.save') }}">
            @csrf
            @method('PUT')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Email Transactional</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info py-2">
                        Konfigurasi ini dipakai untuk email dokumen accounting ke customer seperti quotation, sales order, invoice, pengingat pembayaran, dan tanda terima pembayaran.
                    </div>
                    <label class="form-check mb-3">
                        <input type="hidden" name="is_enabled" value="0">
                        <input class="form-check-input" type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $setting->is_enabled))>
                        <span class="form-check-label">Aktifkan transactional email tenant</span>
                    </label>
                    <div class="mb-3">
                        <label class="form-label">Mode Pengiriman</label>
                        <select name="delivery_mode" class="form-select">
                            <option value="{{ \App\Models\TenantTransactionalMailSetting::DELIVERY_MODE_MANAGED }}"
                                @selected($selectedMode === \App\Models\TenantTransactionalMailSetting::DELIVERY_MODE_MANAGED)
                                @disabled(!$capabilities['managed'])>
                                Email Terkelola
                            </option>
                            <option value="{{ \App\Models\TenantTransactionalMailSetting::DELIVERY_MODE_CUSTOM_SMTP }}"
                                @selected($selectedMode === \App\Models\TenantTransactionalMailSetting::DELIVERY_MODE_CUSTOM_SMTP)
                                @disabled(!$capabilities['custom_smtp'])>
                                SMTP Sendiri
                            </option>
                        </select>
                        <div class="form-hint">
                            Email Terkelola memakai layanan kirim email sistem dengan kuota sesuai plan. SMTP Sendiri memakai server email milik tenant.
                        </div>
                    </div>

                    @if($selectedMode === \App\Models\TenantTransactionalMailSetting::DELIVERY_MODE_MANAGED)
                        <div class="alert alert-secondary py-2">
                            <div class="fw-semibold">Email Terkelola aktif</div>
                            <div class="small">Alamat pengirim memakai sender sistem. Anda tetap bisa mengatur nama pengirim dan email balasan tenant.</div>
                        </div>
                    @endif

                    @if($managedQuota)
                        @php
                            $quotaBadge = match($managedQuota['status']) {
                                'over_limit' => 'bg-red-lt text-red',
                                'at_limit' => 'bg-orange-lt text-orange',
                                'near_limit' => 'bg-yellow-lt text-yellow',
                                default => 'bg-green-lt text-green',
                            };
                        @endphp
                        <div class="mb-3">
                            <div class="text-muted small mb-1">Kuota Email Terkelola Bulanan</div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge {{ $quotaBadge }}">{{ strtoupper($managedQuota['status']) }}</span>
                                <span class="small">{{ $managedQuota['usage'] }} / {{ $managedQuota['limit'] ?? 'Tak terbatas' }} email</span>
                                @if(!is_null($managedQuota['remaining']))
                                    <span class="small text-muted">Sisa {{ $managedQuota['remaining'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">From Name</label>
                            <input type="text" name="from_name" class="form-control" value="{{ old('from_name', $setting->from_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reply-To</label>
                            <input type="email" name="reply_to" class="form-control" value="{{ old('reply_to', $setting->reply_to) }}">
                        </div>

                        @if($selectedMode === \App\Models\TenantTransactionalMailSetting::DELIVERY_MODE_CUSTOM_SMTP)
                            <div class="col-md-6">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" name="smtp_host" class="form-control" value="{{ old('smtp_host', $setting->smtp_host) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Port</label>
                                <input type="number" name="smtp_port" class="form-control" value="{{ old('smtp_port', $setting->smtp_port ?: 587) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Encryption</label>
                                <select name="smtp_encryption" class="form-select">
                                    @foreach(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('smtp_encryption', $setting->smtp_encryption ?: 'tls') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" name="smtp_username" class="form-control" value="{{ old('smtp_username', $setting->smtp_username) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" name="smtp_password" class="form-control" placeholder="{{ $setting->exists ? 'Kosongkan jika tidak berubah' : '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">From Email</label>
                                <input type="email" name="from_email" class="form-control" value="{{ old('from_email', $setting->from_email) }}">
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Test Email</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.transactional-email.test') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Kirim test ke email</label>
                        <input type="email" name="test_email" class="form-control" value="{{ old('test_email', auth()->user()->email ?? '') }}" required>
                    </div>
                    <div class="form-hint mb-3">
                        Test email mengikuti mode pengiriman yang sedang aktif.
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100">Kirim Test Email</button>
                </form>
                @if($setting->last_tested_at)
                    <hr>
                    <div class="small text-muted">Tes terakhir: {{ $setting->last_tested_at->format('d M Y H:i') }}</div>
                    <div class="small mt-1">
                        Status:
                        <span class="badge {{ $setting->last_test_status === 'success' ? 'bg-green-lt text-green' : 'bg-red-lt text-red' }}">
                            {{ strtoupper((string) $setting->last_test_status) }}
                        </span>
                    </div>
                    @if($setting->last_test_error)
                        <div class="small text-danger mt-2">{{ $setting->last_test_error }}</div>
                    @endif
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Mail Logs</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th>Dokumen</th>
                                <th>Penerima</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactionalMailLogs as $log)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $log->subject }}</div>
                                        <div class="text-muted small">{{ $log->document_type }} #{{ $log->document_id ?: '-' }}</div>
                                        <div class="text-muted small">{{ $log->mailer_source === 'managed' ? 'Email Terkelola' : 'SMTP Sendiri' }}</div>
                                    </td>
                                    <td>{{ $log->recipient_email }}</td>
                                    <td>
                                        <span class="badge {{
                                            $log->status === 'sent' ? 'bg-green-lt text-green' : ($log->status === 'failed' ? 'bg-red-lt text-red' : 'bg-yellow-lt text-yellow')
                                        }}">{{ strtoupper($log->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Belum ada log pengiriman.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
