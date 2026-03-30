{{-- ─── Pratinjau: summary bar ─── --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
        <h3 class="card-title mb-0">Pratinjau Numbering</h3>
        <span class="badge bg-blue-lt text-blue">{{ optional($currentCompany)->name ?? 'Belum ada company dipilih' }}</span>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-4">

            <div>
                <div class="text-muted text-uppercase small fw-bold mb-1">Nomor Company</div>
                <div class="fs-4 fw-bold">{{ $documentPreview['company_sale_number'] }}</div>
                <div class="text-muted" style="font-size:.75rem;">Fallback jika branch tidak override</div>
            </div>

            <div class="vr d-none d-sm-block mx-1"></div>

            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="text-muted text-uppercase small fw-bold">Override Branch</div>
                    @if($documentPreview['has_branch_override'])
                        <span class="badge bg-yellow-lt text-yellow">Terkonfigurasi</span>
                    @elseif($documentPreview['branch_selected'])
                        <span class="badge bg-secondary-lt text-secondary">Ikuti company</span>
                    @else
                        <span class="badge bg-secondary-lt text-secondary">Belum ada branch dipilih</span>
                    @endif
                </div>
                <div class="fs-4 fw-bold">{{ $documentPreview['branch_sale_number'] ?: 'Ikuti default company' }}</div>
                <div class="text-muted" style="font-size:.75rem;">
                    {{ $documentPreview['branch_selected'] ? 'Override untuk branch aktif yang dipilih' : 'Pilih branch aktif untuk konfigurasi khusus' }}
                </div>
            </div>

            <div class="vr d-none d-sm-block mx-1"></div>

            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="text-muted text-uppercase small fw-bold">Nomor Efektif</div>
                    <span class="badge bg-azure-lt text-azure">{{ $documentPreview['effective_source'] }}</span>
                </div>
                <div class="fs-4 fw-bold text-primary">{{ $documentPreview['effective_sale_number'] }}</div>
                <div class="text-muted" style="font-size:.75rem;">{{ $documentPreview['effective_applies_to'] }} sekarang memakai setting ini</div>
            </div>

            <div class="vr d-none d-sm-block mx-1"></div>

            <div>
                <div class="text-muted text-uppercase small fw-bold mb-1">Periode Reset</div>
                <div class="fw-semibold">{{ \Illuminate\Support\Str::headline((string) $documentPreview['effective_reset_period']) }}</div>
                <div class="text-muted" style="font-size:.75rem;">Mengikuti sumber efektif di atas</div>
            </div>

        </div>

        @if(!empty($documentPreview['effective_header']) || !empty($documentPreview['effective_footer']) || !empty($documentPreview['effective_receipt_footer']))
        <div class="border-top mt-3 pt-3 d-flex flex-wrap gap-4">
            @if($documentPreview['effective_header'])
            <div style="max-width:26rem;">
                <div class="text-muted text-uppercase small fw-bold mb-1">Header</div>
                <div class="small text-muted">{!! nl2br(e($documentPreview['effective_header'])) !!}</div>
            </div>
            @endif
            @if($documentPreview['effective_footer'])
            <div style="max-width:26rem;">
                <div class="text-muted text-uppercase small fw-bold mb-1">Footer Invoice</div>
                <div class="small text-muted">{!! nl2br(e($documentPreview['effective_footer'])) !!}</div>
            </div>
            @endif
            @if($documentPreview['effective_receipt_footer'])
            <div style="max-width:26rem;">
                <div class="text-muted text-uppercase small fw-bold mb-1">Footer Struk</div>
                <div class="small text-muted">{!! nl2br(e($documentPreview['effective_receipt_footer'])) !!}</div>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>

{{-- ─── Form ─── --}}
@if(!$currentCompany)
    <div class="alert alert-warning">Pilih company aktif terlebih dahulu untuk mengatur pengaturan dokumen.</div>
@else
<div class="alert alert-info mb-4">
    <div class="fw-semibold">Status rollout saat ini</div>
    <div class="small mt-1">{{ $documentPreview['effective_applies_to'] }} sudah membaca setting ini. {{ $documentPreview['pending_applies_to'] }}.</div>
</div>

<form method="POST" action="{{ route('settings.documents.save') }}">
    @csrf
    @method('PUT')

    <div class="row g-4">
        {{-- Default Company --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Default Company</h3>
                        <div class="text-muted small mt-1">{{ $currentCompany->name }}</div>
                    </div>
                    <span class="badge bg-azure-lt text-azure">Fallback branch</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Prefix Invoice</label>
                            <input type="text" name="company_invoice_prefix" class="form-control"
                                   value="{{ old('company_invoice_prefix', optional($companyDocumentSetting)->invoice_prefix) }}"
                                   placeholder="SAL-">
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Padding</label>
                            <input type="number" name="company_invoice_padding" class="form-control"
                                   min="1" max="12"
                                   value="{{ old('company_invoice_padding', optional($companyDocumentSetting)->invoice_padding ?: 5) }}"
                                   required>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">No. Berikutnya</label>
                            <input type="number" name="company_invoice_next_number" class="form-control"
                                   min="1"
                                   value="{{ old('company_invoice_next_number', optional($companyDocumentSetting)->invoice_next_number ?: 1) }}"
                                   required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Periode Reset</label>
                            <select name="company_invoice_reset_period" class="form-select">
                                <option value="never"   @selected(old('company_invoice_reset_period', optional($companyDocumentSetting)->invoice_reset_period ?: 'never') === 'never')>Tidak pernah</option>
                                <option value="monthly" @selected(old('company_invoice_reset_period', optional($companyDocumentSetting)->invoice_reset_period) === 'monthly')>Bulanan</option>
                                <option value="yearly"  @selected(old('company_invoice_reset_period', optional($companyDocumentSetting)->invoice_reset_period) === 'yearly')>Tahunan</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Header Dokumen</label>
                            <textarea name="company_document_header" class="form-control" rows="3">{{ old('company_document_header', optional($companyDocumentSetting)->document_header) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Footer Invoice</label>
                            <textarea name="company_document_footer" class="form-control" rows="2">{{ old('company_document_footer', optional($companyDocumentSetting)->document_footer) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Footer Struk</label>
                            <textarea name="company_receipt_footer" class="form-control" rows="2">{{ old('company_receipt_footer', optional($companyDocumentSetting)->receipt_footer) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="company_notes" class="form-control" rows="2">{{ old('company_notes', optional($companyDocumentSetting)->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Override Branch --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Override Branch</h3>
                        <div class="text-muted small mt-1">{{ optional($currentBranch)->name ?? 'Belum ada branch dipilih' }}</div>
                    </div>
                    <span class="badge {{ $currentBranch ? 'bg-yellow-lt text-yellow' : 'bg-secondary-lt text-secondary' }}">
                        {{ $currentBranch ? 'Override aktif' : 'Opsional' }}
                    </span>
                </div>
                <div class="card-body">
                    @if(!$currentBranch)
                        <div class="alert alert-secondary mb-0">
                            Branch masih optional. Pilih branch aktif melalui switcher di atas untuk mengatur numbering khusus outlet tertentu.
                        </div>
                    @else
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Prefix Invoice</label>
                                <input type="text" name="branch_invoice_prefix" class="form-control"
                                       value="{{ old('branch_invoice_prefix', optional($branchDocumentSetting)->invoice_prefix) }}"
                                       placeholder="Ikuti company">
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">Padding</label>
                                <input type="number" name="branch_invoice_padding" class="form-control"
                                       min="1" max="12"
                                       value="{{ old('branch_invoice_padding', optional($branchDocumentSetting)->invoice_padding ?: 5) }}">
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">No. Berikutnya</label>
                                <input type="number" name="branch_invoice_next_number" class="form-control"
                                       min="1"
                                       value="{{ old('branch_invoice_next_number', optional($branchDocumentSetting)->invoice_next_number ?: 1) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Periode Reset</label>
                                <select name="branch_invoice_reset_period" class="form-select">
                                    <option value="">Ikuti company</option>
                                    <option value="never"   @selected(old('branch_invoice_reset_period', optional($branchDocumentSetting)->invoice_reset_period) === 'never')>Tidak pernah</option>
                                    <option value="monthly" @selected(old('branch_invoice_reset_period', optional($branchDocumentSetting)->invoice_reset_period) === 'monthly')>Bulanan</option>
                                    <option value="yearly"  @selected(old('branch_invoice_reset_period', optional($branchDocumentSetting)->invoice_reset_period) === 'yearly')>Tahunan</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Header Dokumen</label>
                                <textarea name="branch_document_header" class="form-control" rows="3">{{ old('branch_document_header', optional($branchDocumentSetting)->document_header) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Footer Invoice</label>
                                <textarea name="branch_document_footer" class="form-control" rows="2">{{ old('branch_document_footer', optional($branchDocumentSetting)->document_footer) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Footer Struk</label>
                                <textarea name="branch_receipt_footer" class="form-control" rows="2">{{ old('branch_receipt_footer', optional($branchDocumentSetting)->receipt_footer) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="branch_notes" class="form-control" rows="2">{{ old('branch_notes', optional($branchDocumentSetting)->notes) }}</textarea>
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
