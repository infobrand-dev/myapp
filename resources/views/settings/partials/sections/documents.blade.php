<div class="row g-3">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Pratinjau</h3>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <div class="border rounded-3 p-3">
                    <div class="text-secondary text-uppercase small fw-bold">Nomor Dokumen Company</div>
                    <div class="fs-3 fw-bold mt-1">{{ $documentPreview['company_sale_number'] }}</div>
                    <div class="text-muted small mt-1">Nomor ini dipakai saat branch tidak mengaktifkan override sendiri.</div>
                </div>
                <div class="border rounded-3 p-3">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div class="text-secondary text-uppercase small fw-bold">Override Branch</div>
                        @if($documentPreview['has_branch_override'])
                            <span class="badge bg-yellow-lt text-yellow">Terkonfigurasi</span>
                        @elseif($documentPreview['branch_selected'])
                            <span class="badge bg-secondary-lt text-secondary">Ikuti company</span>
                        @else
                            <span class="badge bg-secondary-lt text-secondary">Belum ada branch dipilih</span>
                        @endif
                    </div>
                    <div class="fs-3 fw-bold mt-1">{{ $documentPreview['branch_sale_number'] ?: 'Ikuti default company' }}</div>
                    <div class="text-muted small mt-1">
                        @if($documentPreview['branch_selected'])
                            Override hanya berlaku untuk branch aktif yang sedang dipilih.
                        @else
                            Pilih branch aktif jika ingin menyiapkan numbering khusus outlet tertentu.
                        @endif
                    </div>
                </div>
                <div class="border rounded-3 p-3">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div class="text-secondary text-uppercase small fw-bold">Nomor Efektif</div>
                        <span class="badge bg-azure-lt text-azure">{{ $documentPreview['effective_source'] }}</span>
                    </div>
                    <div class="fs-3 fw-bold mt-1">{{ $documentPreview['effective_sale_number'] }}</div>
                    <div class="text-muted small mt-1">{{ $documentPreview['effective_applies_to'] }} sekarang sudah memakai setting ini.</div>
                </div>
                <div class="border rounded-3 p-3">
                    <div class="text-secondary text-uppercase small fw-bold">Periode Reset</div>
                    <div class="fw-semibold mt-1">{{ \Illuminate\Support\Str::headline((string) $documentPreview['effective_reset_period']) }}</div>
                    <div class="text-muted small mt-1">Counter preview mengikuti sumber setting efektif di atas.</div>
                </div>
                <div class="border rounded-3 p-3">
                    <div class="text-secondary text-uppercase small fw-bold">Output Efektif</div>
                    <div class="small mt-2">
                        <div class="fw-semibold mb-1">Header</div>
                        <div class="text-muted">{!! nl2br(e($documentPreview['effective_header'] ?: 'Belum diisi.')) !!}</div>
                    </div>
                    <div class="small mt-3">
                        <div class="fw-semibold mb-1">Footer Invoice</div>
                        <div class="text-muted">{!! nl2br(e($documentPreview['effective_footer'] ?: 'Belum diisi.')) !!}</div>
                    </div>
                    <div class="small mt-3">
                        <div class="fw-semibold mb-1">Footer Struk</div>
                        <div class="text-muted">{!! nl2br(e($documentPreview['effective_receipt_footer'] ?: 'Belum diisi.')) !!}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h3 class="card-title mb-0">Pengaturan Dokumen</h3>
                    <div class="text-muted small mt-1">Invoice numbering dan template dokumen sekarang sudah punya persistence per company, dengan optional override per branch aktif.</div>
                </div>
                <span class="badge bg-blue-lt text-blue">{{ optional($currentCompany)->name ?? 'Belum ada company dipilih' }}</span>
            </div>
            <div class="card-body">
                @if(!$currentCompany)
                    <div class="alert alert-warning mb-0">Pilih company aktif terlebih dahulu untuk mengatur pengaturan dokumen.</div>
                @else
                    <div class="alert alert-info">
                        <div class="fw-semibold">Status rollout saat ini</div>
                        <div class="small mt-1">{{ $documentPreview['effective_applies_to'] }} sudah membaca setting ini. {{ $documentPreview['pending_applies_to'] }}.</div>
                    </div>
                    <form method="POST" action="{{ route('settings.documents.save') }}" class="row g-4">
                @csrf
                @method('PUT')

                <div class="col-xl-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                            <div>
                                <div class="fw-semibold">Default Company</div>
                                <div class="text-muted small">{{ $currentCompany->name }}</div>
                            </div>
                            <span class="badge bg-azure-lt text-azure">Fallback branch</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Prefix Invoice</label>
                                <input type="text" name="company_invoice_prefix" class="form-control" value="{{ old('company_invoice_prefix', optional($companyDocumentSetting)->invoice_prefix) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Padding</label>
                                <input type="number" name="company_invoice_padding" class="form-control" min="1" max="12" value="{{ old('company_invoice_padding', optional($companyDocumentSetting)->invoice_padding ?: 5) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">No. Berikutnya</label>
                                <input type="number" name="company_invoice_next_number" class="form-control" min="1" value="{{ old('company_invoice_next_number', optional($companyDocumentSetting)->invoice_next_number ?: 1) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Periode Reset</label>
                                <select name="company_invoice_reset_period" class="form-select">
                                    <option value="never" @selected(old('company_invoice_reset_period', optional($companyDocumentSetting)->invoice_reset_period ?: 'never') === 'never')>Tidak pernah</option>
                                    <option value="monthly" @selected(old('company_invoice_reset_period', optional($companyDocumentSetting)->invoice_reset_period) === 'monthly')>Bulanan</option>
                                    <option value="yearly" @selected(old('company_invoice_reset_period', optional($companyDocumentSetting)->invoice_reset_period) === 'yearly')>Tahunan</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Header Dokumen</label>
                                <textarea name="company_document_header" class="form-control" rows="3">{{ old('company_document_header', optional($companyDocumentSetting)->document_header) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Footer Invoice</label>
                                <textarea name="company_document_footer" class="form-control" rows="3">{{ old('company_document_footer', optional($companyDocumentSetting)->document_footer) }}</textarea>
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

                <div class="col-xl-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                            <div>
                                <div class="fw-semibold">Override Branch</div>
                                <div class="text-muted small">{{ optional($currentBranch)->name ?? 'Belum ada branch dipilih' }}</div>
                            </div>
                            <span class="badge bg-yellow-lt text-yellow">{{ $currentBranch ? 'Override aktif' : 'Opsional' }}</span>
                        </div>

                        @if(!$currentBranch)
                            <div class="alert alert-secondary mb-0">Branch masih optional. Jika memilih branch aktif, form override di sisi ini akan menyimpan setting khusus branch tersebut.</div>
                        @else
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Prefix Invoice</label>
                                    <input type="text" name="branch_invoice_prefix" class="form-control" value="{{ old('branch_invoice_prefix', optional($branchDocumentSetting)->invoice_prefix) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Padding</label>
                                    <input type="number" name="branch_invoice_padding" class="form-control" min="1" max="12" value="{{ old('branch_invoice_padding', optional($branchDocumentSetting)->invoice_padding ?: 5) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">No. Berikutnya</label>
                                    <input type="number" name="branch_invoice_next_number" class="form-control" min="1" value="{{ old('branch_invoice_next_number', optional($branchDocumentSetting)->invoice_next_number ?: 1) }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Periode Reset</label>
                                    <select name="branch_invoice_reset_period" class="form-select">
                                        <option value="">Ikuti company</option>
                                        <option value="never" @selected(old('branch_invoice_reset_period', optional($branchDocumentSetting)->invoice_reset_period) === 'never')>Tidak pernah</option>
                                        <option value="monthly" @selected(old('branch_invoice_reset_period', optional($branchDocumentSetting)->invoice_reset_period) === 'monthly')>Bulanan</option>
                                        <option value="yearly" @selected(old('branch_invoice_reset_period', optional($branchDocumentSetting)->invoice_reset_period) === 'yearly')>Tahunan</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Header Dokumen</label>
                                    <textarea name="branch_document_header" class="form-control" rows="3">{{ old('branch_document_header', optional($branchDocumentSetting)->document_header) }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Footer Invoice</label>
                                    <textarea name="branch_document_footer" class="form-control" rows="3">{{ old('branch_document_footer', optional($branchDocumentSetting)->document_footer) }}</textarea>
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

                    @can('settings.manage')
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">
                                <i class="ti ti-device-floppy me-1"></i>Simpan Pengaturan Dokumen
                            </button>
                        </div>
                    @endcan
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
