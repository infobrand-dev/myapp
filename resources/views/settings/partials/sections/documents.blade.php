<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center gap-3">
        <div>
            <h3 class="card-title mb-0">Document Settings</h3>
            <div class="text-muted small mt-1">Invoice numbering dan template dokumen sekarang sudah punya persistence per company, dengan optional override per branch aktif.</div>
        </div>
        <span class="badge bg-blue-lt text-blue">{{ optional($currentCompany)->name ?? 'No company selected' }}</span>
    </div>
    <div class="card-body">
        @if(!$currentCompany)
            <div class="alert alert-warning mb-0">Pilih company aktif terlebih dahulu untuk mengatur document settings.</div>
        @else
            <form method="POST" action="{{ route('settings.documents.save') }}" class="row g-4">
                @csrf
                @method('PUT')

                <div class="col-xl-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                            <div>
                                <div class="fw-semibold">Company Defaults</div>
                                <div class="text-muted small">{{ $currentCompany->name }}</div>
                            </div>
                            <span class="badge bg-azure-lt text-azure">Branch fallback source</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Invoice Prefix</label>
                                <input type="text" name="company_invoice_prefix" class="form-control" value="{{ old('company_invoice_prefix', optional($companyDocumentSetting)->invoice_prefix) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Padding</label>
                                <input type="number" name="company_invoice_padding" class="form-control" min="1" max="12" value="{{ old('company_invoice_padding', optional($companyDocumentSetting)->invoice_padding ?: 5) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Next No.</label>
                                <input type="number" name="company_invoice_next_number" class="form-control" min="1" value="{{ old('company_invoice_next_number', optional($companyDocumentSetting)->invoice_next_number ?: 1) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reset Period</label>
                                <select name="company_invoice_reset_period" class="form-select">
                                    <option value="never" @selected(old('company_invoice_reset_period', optional($companyDocumentSetting)->invoice_reset_period ?: 'never') === 'never')>Never</option>
                                    <option value="monthly" @selected(old('company_invoice_reset_period', optional($companyDocumentSetting)->invoice_reset_period) === 'monthly')>Monthly</option>
                                    <option value="yearly" @selected(old('company_invoice_reset_period', optional($companyDocumentSetting)->invoice_reset_period) === 'yearly')>Yearly</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Document Header</label>
                                <textarea name="company_document_header" class="form-control" rows="3">{{ old('company_document_header', optional($companyDocumentSetting)->document_header) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Document Footer</label>
                                <textarea name="company_document_footer" class="form-control" rows="3">{{ old('company_document_footer', optional($companyDocumentSetting)->document_footer) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Receipt Footer</label>
                                <textarea name="company_receipt_footer" class="form-control" rows="2">{{ old('company_receipt_footer', optional($companyDocumentSetting)->receipt_footer) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="company_notes" class="form-control" rows="2">{{ old('company_notes', optional($companyDocumentSetting)->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                            <div>
                                <div class="fw-semibold">Branch Override</div>
                                <div class="text-muted small">{{ optional($currentBranch)->name ?? 'No branch selected' }}</div>
                            </div>
                            <span class="badge bg-yellow-lt text-yellow">{{ $currentBranch ? 'Override active' : 'Optional' }}</span>
                        </div>

                        @if(!$currentBranch)
                            <div class="alert alert-secondary mb-0">Branch masih optional. Jika memilih branch aktif, form override di sisi ini akan menyimpan setting khusus branch tersebut.</div>
                        @else
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Invoice Prefix</label>
                                    <input type="text" name="branch_invoice_prefix" class="form-control" value="{{ old('branch_invoice_prefix', optional($branchDocumentSetting)->invoice_prefix) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Padding</label>
                                    <input type="number" name="branch_invoice_padding" class="form-control" min="1" max="12" value="{{ old('branch_invoice_padding', optional($branchDocumentSetting)->invoice_padding ?: 5) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Next No.</label>
                                    <input type="number" name="branch_invoice_next_number" class="form-control" min="1" value="{{ old('branch_invoice_next_number', optional($branchDocumentSetting)->invoice_next_number ?: 1) }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Reset Period</label>
                                    <select name="branch_invoice_reset_period" class="form-select">
                                        <option value="">Follow company</option>
                                        <option value="never" @selected(old('branch_invoice_reset_period', optional($branchDocumentSetting)->invoice_reset_period) === 'never')>Never</option>
                                        <option value="monthly" @selected(old('branch_invoice_reset_period', optional($branchDocumentSetting)->invoice_reset_period) === 'monthly')>Monthly</option>
                                        <option value="yearly" @selected(old('branch_invoice_reset_period', optional($branchDocumentSetting)->invoice_reset_period) === 'yearly')>Yearly</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Document Header</label>
                                    <textarea name="branch_document_header" class="form-control" rows="3">{{ old('branch_document_header', optional($branchDocumentSetting)->document_header) }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Document Footer</label>
                                    <textarea name="branch_document_footer" class="form-control" rows="3">{{ old('branch_document_footer', optional($branchDocumentSetting)->document_footer) }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Receipt Footer</label>
                                    <textarea name="branch_receipt_footer" class="form-control" rows="2">{{ old('branch_receipt_footer', optional($branchDocumentSetting)->receipt_footer) }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="branch_notes" class="form-control" rows="2">{{ old('branch_notes', optional($branchDocumentSetting)->notes) }}</textarea>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @can('settings.manage')
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Save Document Settings</button>
                    </div>
                @endcan
            </form>
        @endif
    </div>
</div>
