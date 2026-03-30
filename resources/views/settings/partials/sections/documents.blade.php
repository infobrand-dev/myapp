{{-- ══════════════════════════════════════════════
     PRATINJAU — 4-column grid, no flex-wrap chaos
     ══════════════════════════════════════════════ --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Pratinjau Numbering</h3>
        <span class="badge bg-blue-lt text-blue">{{ optional($currentCompany)->name ?? 'Belum ada company' }}</span>
    </div>
    <div class="row g-0 border-top">

        {{-- Cell 1: Nomor Company --}}
        <div class="col-6 col-lg-3 p-3 border-bottom border-end">
            <div class="doc-preview-label">Nomor Company</div>
            <div class="doc-preview-value">{{ $documentPreview['company_sale_number'] }}</div>
            <div class="doc-preview-hint">Fallback jika branch tidak override</div>
        </div>

        {{-- Cell 2: Override Branch --}}
        <div class="col-6 col-lg-3 p-3 border-bottom border-end">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="doc-preview-label mb-0">Override Branch</span>
                @if($documentPreview['has_branch_override'])
                    <span class="badge bg-yellow-lt text-yellow" style="font-size:.6rem;">Aktif</span>
                @else
                    <span class="badge bg-secondary-lt text-secondary" style="font-size:.6rem;">–</span>
                @endif
            </div>
            <div class="doc-preview-value">{{ $documentPreview['branch_sale_number'] ?: '–' }}</div>
            <div class="doc-preview-hint">
                {{ $documentPreview['branch_selected'] ? 'Khusus branch aktif' : 'Pilih branch untuk override' }}
            </div>
        </div>

        {{-- Cell 3: Nomor Efektif --}}
        <div class="col-6 col-lg-3 p-3 border-bottom border-end">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="doc-preview-label mb-0">Nomor Efektif</span>
                <span class="badge bg-azure-lt text-azure" style="font-size:.6rem;">{{ $documentPreview['effective_source'] }}</span>
            </div>
            <div class="doc-preview-value text-primary">{{ $documentPreview['effective_sale_number'] }}</div>
            <div class="doc-preview-hint">{{ $documentPreview['effective_applies_to'] }}</div>
        </div>

        {{-- Cell 4: Periode Reset --}}
        <div class="col-6 col-lg-3 p-3 border-bottom">
            <div class="doc-preview-label">Periode Reset</div>
            <div class="doc-preview-value">{{ \Illuminate\Support\Str::headline((string) $documentPreview['effective_reset_period']) }}</div>
            <div class="doc-preview-hint">Mengikuti sumber efektif</div>
        </div>

    </div>

    {{-- Output efektif: hanya tampil jika ada konten --}}
    @if(!empty($documentPreview['effective_header']) || !empty($documentPreview['effective_footer']) || !empty($documentPreview['effective_receipt_footer']))
    <div class="card-body pt-0">
        <div class="border-top pt-3">
            <div class="text-muted text-uppercase small fw-bold mb-2">Output Efektif</div>
            <div class="row g-3">
                @if($documentPreview['effective_header'])
                <div class="col-md-4">
                    <div class="text-muted small fw-semibold mb-1">Header</div>
                    <div class="small bg-light rounded p-2" style="white-space:pre-wrap;">{{ $documentPreview['effective_header'] }}</div>
                </div>
                @endif
                @if($documentPreview['effective_footer'])
                <div class="col-md-4">
                    <div class="text-muted small fw-semibold mb-1">Footer Invoice</div>
                    <div class="small bg-light rounded p-2" style="white-space:pre-wrap;">{{ $documentPreview['effective_footer'] }}</div>
                </div>
                @endif
                @if($documentPreview['effective_receipt_footer'])
                <div class="col-md-4">
                    <div class="text-muted small fw-semibold mb-1">Footer Struk</div>
                    <div class="small bg-light rounded p-2" style="white-space:pre-wrap;">{{ $documentPreview['effective_receipt_footer'] }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════
     FORM
     ══════════════════════════════════════════════ --}}
@if(!$currentCompany)
    <div class="alert alert-warning mb-0">
        <i class="ti ti-alert-triangle me-2"></i>
        Pilih company aktif terlebih dahulu untuk mengatur pengaturan dokumen.
    </div>
@else

{{-- Status rollout --}}
<div class="d-flex align-items-start gap-3 p-3 mb-4 border rounded-3 bg-blue-lt">
    <i class="ti ti-info-circle text-azure mt-1 flex-shrink-0"></i>
    <div>
        <div class="fw-semibold text-azure" style="font-size:.875rem;">Status Rollout</div>
        <div class="small text-muted mt-1">{{ $documentPreview['effective_applies_to'] }} sudah membaca setting ini. {{ $documentPreview['pending_applies_to'] }}.</div>
    </div>
</div>

<form method="POST" action="{{ route('settings.documents.save') }}">
    @csrf
    @method('PUT')

    <div class="row g-4">

        {{-- ── Default Company ── --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">Default Company</h3>
                            <div class="text-muted small mt-1">{{ $currentCompany->name }}</div>
                        </div>
                        <span class="badge bg-azure-lt text-azure">Fallback</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        {{-- Numbering row --}}
                        <div class="col-12">
                            <label class="form-label">Prefix Invoice</label>
                            <input type="text" name="company_invoice_prefix"
                                   class="form-control"
                                   value="{{ old('company_invoice_prefix', optional($companyDocumentSetting)->invoice_prefix) }}"
                                   placeholder="Contoh: SAL-">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Padding Angka</label>
                            <input type="number" name="company_invoice_padding"
                                   class="form-control"
                                   min="1" max="12"
                                   value="{{ old('company_invoice_padding', optional($companyDocumentSetting)->invoice_padding ?: 5) }}"
                                   required>
                            <div class="form-hint">Jumlah digit (contoh: 5 → 00001)</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">No. Berikutnya</label>
                            <input type="number" name="company_invoice_next_number"
                                   class="form-control"
                                   min="1"
                                   value="{{ old('company_invoice_next_number', optional($companyDocumentSetting)->invoice_next_number ?: 1) }}"
                                   required>
                            <div class="form-hint">Counter dimulai dari sini</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Periode Reset Counter</label>
                            <select name="company_invoice_reset_period" class="form-select">
                                <option value="never"   @selected(old('company_invoice_reset_period', optional($companyDocumentSetting)->invoice_reset_period ?: 'never') === 'never')>Tidak pernah</option>
                                <option value="monthly" @selected(old('company_invoice_reset_period', optional($companyDocumentSetting)->invoice_reset_period) === 'monthly')>Reset setiap bulan</option>
                                <option value="yearly"  @selected(old('company_invoice_reset_period', optional($companyDocumentSetting)->invoice_reset_period) === 'yearly')>Reset setiap tahun</option>
                            </select>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-12">
                            <label class="form-label">Header Dokumen</label>
                            <textarea name="company_document_header" class="form-control" rows="3"
                                      placeholder="Nama perusahaan, alamat, dll.">{{ old('company_document_header', optional($companyDocumentSetting)->document_header) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Footer Invoice</label>
                            <textarea name="company_document_footer" class="form-control" rows="2"
                                      placeholder="Pesan terima kasih, syarat pembayaran, dll.">{{ old('company_document_footer', optional($companyDocumentSetting)->document_footer) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Footer Struk</label>
                            <textarea name="company_receipt_footer" class="form-control" rows="2"
                                      placeholder="Pesan untuk struk kasir.">{{ old('company_receipt_footer', optional($companyDocumentSetting)->receipt_footer) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Internal</label>
                            <textarea name="company_notes" class="form-control" rows="2"
                                      placeholder="Catatan internal, tidak tampil di dokumen.">{{ old('company_notes', optional($companyDocumentSetting)->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Override Branch ── --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">Override Branch</h3>
                            <div class="text-muted small mt-1">
                                {{ optional($currentBranch)->name ?? 'Belum ada branch dipilih' }}
                            </div>
                        </div>
                        <span class="badge {{ $currentBranch ? 'bg-yellow-lt text-yellow' : 'bg-secondary-lt text-secondary' }}">
                            {{ $currentBranch ? 'Override aktif' : 'Opsional' }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    @if(!$currentBranch)
                        <div class="text-center py-4">
                            <i class="ti ti-building d-block mx-auto mb-2"
                               style="font-size:2.5rem; color:var(--brand-gray-300);"></i>
                            <div class="fw-semibold text-muted mb-1">Belum ada branch dipilih</div>
                            <div class="text-muted small">
                                Pilih branch aktif melalui switcher di topbar untuk mengatur
                                numbering dan template khusus outlet tertentu.
                            </div>
                        </div>
                    @else
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Prefix Invoice</label>
                                <input type="text" name="branch_invoice_prefix"
                                       class="form-control"
                                       value="{{ old('branch_invoice_prefix', optional($branchDocumentSetting)->invoice_prefix) }}"
                                       placeholder="Kosong = ikuti company">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Padding Angka</label>
                                <input type="number" name="branch_invoice_padding"
                                       class="form-control"
                                       min="1" max="12"
                                       value="{{ old('branch_invoice_padding', optional($branchDocumentSetting)->invoice_padding ?: 5) }}">
                                <div class="form-hint">Jumlah digit</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">No. Berikutnya</label>
                                <input type="number" name="branch_invoice_next_number"
                                       class="form-control"
                                       min="1"
                                       value="{{ old('branch_invoice_next_number', optional($branchDocumentSetting)->invoice_next_number ?: 1) }}">
                                <div class="form-hint">Counter awal</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Periode Reset Counter</label>
                                <select name="branch_invoice_reset_period" class="form-select">
                                    <option value="">Ikuti company</option>
                                    <option value="never"   @selected(old('branch_invoice_reset_period', optional($branchDocumentSetting)->invoice_reset_period) === 'never')>Tidak pernah</option>
                                    <option value="monthly" @selected(old('branch_invoice_reset_period', optional($branchDocumentSetting)->invoice_reset_period) === 'monthly')>Reset setiap bulan</option>
                                    <option value="yearly"  @selected(old('branch_invoice_reset_period', optional($branchDocumentSetting)->invoice_reset_period) === 'yearly')>Reset setiap tahun</option>
                                </select>
                            </div>

                            <div class="col-12"><hr class="my-1"></div>

                            <div class="col-12">
                                <label class="form-label">Header Dokumen</label>
                                <textarea name="branch_document_header" class="form-control" rows="3"
                                          placeholder="Nama outlet, alamat cabang, dll.">{{ old('branch_document_header', optional($branchDocumentSetting)->document_header) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Footer Invoice</label>
                                <textarea name="branch_document_footer" class="form-control" rows="2"
                                          placeholder="Pesan khusus cabang ini.">{{ old('branch_document_footer', optional($branchDocumentSetting)->document_footer) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Footer Struk</label>
                                <textarea name="branch_receipt_footer" class="form-control" rows="2"
                                          placeholder="Pesan untuk struk kasir cabang.">{{ old('branch_receipt_footer', optional($branchDocumentSetting)->receipt_footer) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan Internal</label>
                                <textarea name="branch_notes" class="form-control" rows="2"
                                          placeholder="Catatan internal cabang.">{{ old('branch_notes', optional($branchDocumentSetting)->notes) }}</textarea>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @can('settings.manage')
        <div class="col-12">
            <button class="btn btn-primary" type="submit" data-loading="Menyimpan...">
                <i class="ti ti-device-floppy me-1"></i>Simpan Pengaturan Dokumen
            </button>
        </div>
        @endcan
    </div>
</form>
@endif
