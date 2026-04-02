<div class="row g-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Add-on Premium</h3>
            </div>
            <div class="card-body">
                <div class="fw-semibold">Bring Your Own AI</div>
                <div class="text-muted small mt-1">Gunakan API key OpenAI atau provider AI milik Anda sendiri. Biaya token tetap ditagihkan oleh provider Anda, sedangkan platform tetap membatasi orkestrasi, penyimpanan, dan kapasitas penggunaan.</div>
                <div class="alert alert-azure mt-3 mb-0">
                    <div class="fw-semibold">Proses aktivasi</div>
                    <div class="small mt-1">Permintaan BYO AI akan direview manual oleh tim kami. Tim platform akan menghubungi tenant untuk konfirmasi kebutuhan, mengecek kelayakan penggunaan, lalu menginformasikan apakah tenant memenuhi syarat untuk diaktifkan.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between gap-3">
                <h3 class="card-title mb-0">Status BYO AI</h3>
                <span class="badge {{
                    $byoAiEnabled
                        ? 'bg-success-lt text-success'
                        : (optional($byoAiRequest)->status === \App\Support\ByoAiAddon::REQUEST_STATUS_CONTACTING_TENANT
                            ? 'bg-azure-lt text-azure'
                            : (in_array(optional($byoAiRequest)->status, [\App\Support\ByoAiAddon::REQUEST_STATUS_PENDING], true)
                                ? 'bg-warning-lt text-warning'
                                : (in_array(optional($byoAiRequest)->status, [\App\Support\ByoAiAddon::REQUEST_STATUS_REJECTED, \App\Support\ByoAiAddon::REQUEST_STATUS_NOT_ELIGIBLE], true)
                                    ? 'bg-danger-lt text-danger'
                                    : 'bg-secondary-lt text-secondary')))
                }}">
                    {{
                        $byoAiEnabled
                            ? 'Active'
                            : match (optional($byoAiRequest)->status) {
                                \App\Support\ByoAiAddon::REQUEST_STATUS_PENDING => 'Pending review',
                                \App\Support\ByoAiAddon::REQUEST_STATUS_CONTACTING_TENANT => 'Contacting tenant',
                                \App\Support\ByoAiAddon::REQUEST_STATUS_REJECTED => 'Rejected',
                                \App\Support\ByoAiAddon::REQUEST_STATUS_NOT_ELIGIBLE => 'Not eligible',
                                default => 'Not enabled',
                            }
                    }}
                </span>
            </div>
            <div class="card-body">
                @if($byoAiEnabled)
                    <div class="alert alert-success mb-3">
                        <div class="fw-semibold">BYO AI aktif</div>
                        <div class="small mt-1">Add-on sudah aktif untuk tenant ini. Opsi BYO sekarang tersedia di module Chatbot dan penggunaan tetap mengikuti batas add-on platform.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-secondary text-uppercase small fw-bold">Chatbot BYO</div>
                                <div class="fw-semibold mt-2">{{ $byoAiUsageStates['accounts']['usage'] ?? 0 }} / {{ $byoAiUsageStates['accounts']['limit'] ?? 'Unlimited' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-secondary text-uppercase small fw-bold">Request / Bulan</div>
                                <div class="fw-semibold mt-2">{{ $byoAiUsageStates['requests']['usage'] ?? 0 }} / {{ $byoAiUsageStates['requests']['limit'] ?? 'Unlimited' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-secondary text-uppercase small fw-bold">Token / Bulan</div>
                                <div class="fw-semibold mt-2">{{ number_format((int) ($byoAiUsageStates['tokens']['usage'] ?? 0)) }} / {{ $byoAiUsageStates['tokens']['limit'] !== null ? number_format((int) $byoAiUsageStates['tokens']['limit']) : 'Unlimited' }}</div>
                            </div>
                        </div>
                    </div>
                @elseif(optional($byoAiRequest)->status === \App\Support\ByoAiAddon::REQUEST_STATUS_PENDING)
                    <div class="alert alert-warning mb-3">
                        <div class="fw-semibold">Permintaan sedang direview</div>
                        <div class="small mt-1">Tim platform akan menghubungi tenant untuk verifikasi kebutuhan dan menginformasikan apakah tenant memenuhi syarat mendapatkan fitur BYO AI.</div>
                    </div>

                    <div class="small text-muted">
                        <div>Provider pilihan: {{ strtoupper((string) ($byoAiRequest->preferred_provider ?: '-')) }}</div>
                        <div>Volume yang diajukan: {{ $byoAiRequest->intended_volume ?: '-' }}</div>
                        <div>Diajukan pada: {{ optional($byoAiRequest->created_at)->format('d M Y H:i') ?: '-' }}</div>
                    </div>
                @elseif(optional($byoAiRequest)->status === \App\Support\ByoAiAddon::REQUEST_STATUS_CONTACTING_TENANT)
                    <div class="alert alert-azure mb-3">
                        <div class="fw-semibold">Tim platform sedang menghubungi tenant</div>
                        <div class="small mt-1">Permintaan lolos review awal. Tim kami sedang menghubungi tenant untuk klarifikasi kebutuhan dan konfirmasi kelayakan aktivasi.</div>
                    </div>

                    <div class="small text-muted">
                        <div>Provider pilihan: {{ strtoupper((string) ($byoAiRequest->preferred_provider ?: '-')) }}</div>
                        <div>Volume yang diajukan: {{ $byoAiRequest->intended_volume ?: '-' }}</div>
                        <div>Diajukan pada: {{ optional($byoAiRequest->created_at)->format('d M Y H:i') ?: '-' }}</div>
                    </div>
                @elseif(in_array(optional($byoAiRequest)->status, [\App\Support\ByoAiAddon::REQUEST_STATUS_REJECTED, \App\Support\ByoAiAddon::REQUEST_STATUS_NOT_ELIGIBLE], true))
                    <div class="alert alert-danger mb-3">
                        <div class="fw-semibold">{{ optional($byoAiRequest)->status === \App\Support\ByoAiAddon::REQUEST_STATUS_NOT_ELIGIBLE ? 'Tenant belum memenuhi syarat' : 'Permintaan belum disetujui' }}</div>
                        <div class="small mt-1">Saat ini tenant belum dapat diaktifkan untuk BYO AI. Tim platform akan memberikan arahan berikutnya jika diperlukan.</div>
                    </div>

                    @if($byoAiRequest->review_notes)
                        <div class="text-muted small">{{ $byoAiRequest->review_notes }}</div>
                    @endif
                @else
                    <form method="POST" action="{{ route('settings.addons.byo-ai-request') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Provider pilihan</label>
                                <select class="form-select" name="preferred_provider" required>
                                    @foreach($byoAiProviders as $provider)
                                        <option value="{{ $provider }}">{{ strtoupper($provider) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Perkiraan volume</label>
                                <input type="text" class="form-control" name="intended_volume" placeholder="mis. 5.000 auto reply / bulan" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah chatbot account</label>
                                <input type="number" class="form-control" name="chatbot_account_count" min="1" step="1" placeholder="mis. 3">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah channel</label>
                                <input type="number" class="form-control" name="channel_count" min="1" step="1" placeholder="mis. 4">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kontak teknis</label>
                                <input type="text" class="form-control" name="technical_contact_name" placeholder="Nama PIC teknis">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email teknis</label>
                                <input type="email" class="form-control" name="technical_contact_email" placeholder="tech@company.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan kebutuhan</label>
                                <textarea class="form-control" name="notes" rows="3" placeholder="Jelaskan kebutuhan, target volume, atau alasan tenant membutuhkan BYO AI"></textarea>
                            </div>
                        </div>
                        <div class="d-flex align-items-start justify-content-between gap-3 mt-3 flex-column flex-md-row">
                            <div class="text-muted small">
                                Dengan mengirim permintaan ini, tenant memahami bahwa aktivasi tidak otomatis. Tim platform akan melakukan review dan menghubungi tenant jika dibutuhkan informasi tambahan.
                            </div>
                            <button type="submit" class="btn btn-primary flex-shrink-0">Request BYO AI</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
