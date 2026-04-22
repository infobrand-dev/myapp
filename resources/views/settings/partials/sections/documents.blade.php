<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="card-title mb-0">Pratinjau Numbering</h3>
            <div class="text-muted small mt-1">{{ $documentPreview['rollout_summary'] }}</div>
        </div>
        <span class="badge bg-blue-lt text-blue">{{ optional($currentCompany)->name ?? 'Belum ada company' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table mb-0">
            <thead>
                <tr>
                    <th>Dokumen</th>
                    <th>Company</th>
                    <th>Branch</th>
                    <th>Efektif</th>
                    <th>Reset</th>
                </tr>
            </thead>
            <tbody>
                @foreach($documentPreview['numbering_documents'] as $numberingDocument)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $numberingDocument['label'] }}</div>
                            <div class="text-muted small">{{ $numberingDocument['applies_to'] }}</div>
                        </td>
                        <td>{{ $numberingDocument['company_preview'] }}</td>
                        <td>{{ $numberingDocument['branch_preview'] ?: 'Ikuti company' }}</td>
                        <td>
                            <div class="fw-semibold text-primary">{{ $numberingDocument['effective_preview'] }}</div>
                            <div class="text-muted small">{{ $numberingDocument['effective_source'] }}</div>
                        </td>
                        <td>{{ \Illuminate\Support\Str::headline((string) $numberingDocument['effective_reset_period']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(!empty($documentPreview['effective_header']) || !empty($documentPreview['effective_footer']) || !empty($documentPreview['effective_receipt_footer']))
        <div class="card-body border-top">
            <div class="text-muted text-uppercase small fw-bold mb-2">Output Template Efektif</div>
            <div class="row g-3">
                @if($documentPreview['effective_header'])
                    <div class="col-md-4">
                        <div class="text-muted small fw-semibold mb-1">Header</div>
                        <div class="small bg-light rounded p-2" style="white-space:pre-wrap;">{{ $documentPreview['effective_header'] }}</div>
                    </div>
                @endif
                @if($documentPreview['effective_footer'])
                    <div class="col-md-4">
                        <div class="text-muted small fw-semibold mb-1">Footer Dokumen</div>
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
    @endif
</div>

@if(!$currentCompany)
    <div class="alert alert-warning mb-0">
        <i class="ti ti-alert-triangle me-2"></i>
        Pilih company aktif terlebih dahulu untuk mengatur pengaturan dokumen.
    </div>
@else
<div class="d-flex align-items-start gap-3 p-3 mb-4 border rounded-3 bg-blue-lt">
    <i class="ti ti-info-circle text-azure mt-1 flex-shrink-0"></i>
    <div>
        <div class="fw-semibold text-azure" style="font-size:.875rem;">Fondasi Jangka Panjang</div>
        <div class="small text-muted mt-1">{{ $documentPreview['future_summary'] }}</div>
    </div>
</div>

<form method="POST" action="{{ route('settings.documents.save') }}">
    @csrf
    @method('PUT')

    <div class="row g-4">
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
                        @foreach($documentPreview['numbering_documents'] as $numberingDocument)
                            @php($companyRule = $numberingDocument['company_rule'])
                            @php($definition = \App\Models\DocumentNumberingRule::definition($numberingDocument['type']))
                            <div class="col-12 border rounded-3 p-3">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <div class="fw-semibold">{{ $numberingDocument['label'] }}</div>
                                        <div class="text-muted small">{{ $numberingDocument['applies_to'] }}</div>
                                    </div>
                                    <span class="badge bg-secondary-lt text-secondary">{{ $numberingDocument['company_preview'] }}</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Prefix</label>
                                        <input type="text"
                                               name="company_numbering[{{ $numberingDocument['type'] }}][prefix]"
                                               class="form-control"
                                               value="{{ old('company_numbering.'.$numberingDocument['type'].'.prefix', optional($companyRule)->prefix ?: $definition['default_prefix']) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Format Nomor</label>
                                        <input type="text"
                                               name="company_numbering[{{ $numberingDocument['type'] }}][number_format]"
                                               class="form-control"
                                               value="{{ old('company_numbering.'.$numberingDocument['type'].'.number_format', optional($companyRule)->number_format ?: $definition['default_format']) }}">
                                        <div class="form-hint">Token: {PREFIX}, {YYYY}, {YY}, {MM}, {DD}, {YYYYMM}, {YYYYMMDD}, {SEQ}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Padding</label>
                                        <input type="number"
                                               name="company_numbering[{{ $numberingDocument['type'] }}][padding]"
                                               class="form-control"
                                               min="1" max="12"
                                               value="{{ old('company_numbering.'.$numberingDocument['type'].'.padding', optional($companyRule)->padding ?: 5) }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">No. Berikutnya</label>
                                        <input type="number"
                                               name="company_numbering[{{ $numberingDocument['type'] }}][next_number]"
                                               class="form-control"
                                               min="1"
                                               value="{{ old('company_numbering.'.$numberingDocument['type'].'.next_number', optional($companyRule)->next_number ?: 1) }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Reset Counter</label>
                                        <select name="company_numbering[{{ $numberingDocument['type'] }}][reset_period]" class="form-select">
                                            <option value="never" @selected(old('company_numbering.'.$numberingDocument['type'].'.reset_period', optional($companyRule)->reset_period ?: 'never') === 'never')>Tidak pernah</option>
                                            <option value="monthly" @selected(old('company_numbering.'.$numberingDocument['type'].'.reset_period', optional($companyRule)->reset_period) === 'monthly')>Reset setiap bulan</option>
                                            <option value="yearly" @selected(old('company_numbering.'.$numberingDocument['type'].'.reset_period', optional($companyRule)->reset_period) === 'yearly')>Reset setiap tahun</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="col-12"><hr class="my-1"></div>

                        @foreach($documentPreview['numbering_documents'] as $numberingDocument)
                            @php($companyWorkflowRule = $numberingDocument['company_workflow_rule'])
                            <div class="col-12 border rounded-3 p-3">
                                <div class="fw-semibold mb-1">Workflow {{ $numberingDocument['label'] }}</div>
                                <div class="text-muted small mb-3">Atur apakah dokumen ini wajib approval sebelum convert atau finalize.</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-check">
                                            <input type="hidden" name="company_workflow[{{ $numberingDocument['type'] }}][requires_approval_before_conversion]" value="0">
                                            <input class="form-check-input" type="checkbox" name="company_workflow[{{ $numberingDocument['type'] }}][requires_approval_before_conversion]" value="1" @checked(old('company_workflow.'.$numberingDocument['type'].'.requires_approval_before_conversion', optional($companyWorkflowRule)->requires_approval_before_conversion ?? $numberingDocument['requires_approval_before_conversion']))>
                                            <span class="form-check-label">Wajib approval sebelum convert</span>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-check">
                                            <input type="hidden" name="company_workflow[{{ $numberingDocument['type'] }}][requires_approval_before_finalize]" value="0">
                                            <input class="form-check-input" type="checkbox" name="company_workflow[{{ $numberingDocument['type'] }}][requires_approval_before_finalize]" value="1" @checked(old('company_workflow.'.$numberingDocument['type'].'.requires_approval_before_finalize', optional($companyWorkflowRule)->requires_approval_before_finalize ?? false))>
                                            <span class="form-check-label">Wajib approval sebelum finalize/post</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-12">
                            <label class="form-label">Header Dokumen</label>
                            <textarea name="company_document_header" class="form-control" rows="3" placeholder="Nama perusahaan, alamat, dll.">{{ old('company_document_header', optional($companyDocumentSetting)->document_header) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Footer Dokumen</label>
                            <textarea name="company_document_footer" class="form-control" rows="2" placeholder="Pesan terima kasih, syarat pembayaran, dll.">{{ old('company_document_footer', optional($companyDocumentSetting)->document_footer) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Footer Struk</label>
                            <textarea name="company_receipt_footer" class="form-control" rows="2" placeholder="Pesan untuk struk kasir.">{{ old('company_receipt_footer', optional($companyDocumentSetting)->receipt_footer) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Internal</label>
                            <textarea name="company_notes" class="form-control" rows="2" placeholder="Catatan internal, tidak tampil di dokumen.">{{ old('company_notes', optional($companyDocumentSetting)->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">Override Branch</h3>
                            <div class="text-muted small mt-1">{{ optional($currentBranch)->name ?? 'Belum ada branch dipilih' }}</div>
                        </div>
                        <span class="badge {{ $currentBranch ? 'bg-yellow-lt text-yellow' : 'bg-secondary-lt text-secondary' }}">
                            {{ $currentBranch ? 'Override aktif' : 'Opsional' }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    @if(!$currentBranch)
                        <div class="text-center py-4">
                            <i class="ti ti-building d-block mx-auto mb-2" style="font-size:2.5rem; color:var(--brand-gray-300);"></i>
                            <div class="fw-semibold text-muted mb-1">Belum ada branch dipilih</div>
                            <div class="text-muted small">
                                Pilih branch aktif melalui switcher di topbar untuk mengatur numbering dan template khusus outlet tertentu.
                            </div>
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach($documentPreview['numbering_documents'] as $numberingDocument)
                                @php($branchRule = $numberingDocument['branch_rule'])
                                @php($definition = \App\Models\DocumentNumberingRule::definition($numberingDocument['type']))
                                <div class="col-12 border rounded-3 p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <div class="fw-semibold">{{ $numberingDocument['label'] }}</div>
                                            <div class="text-muted small">Override branch akan menjadi source of truth untuk branch aktif ini.</div>
                                        </div>
                                        <span class="badge {{ $branchRule ? 'bg-yellow-lt text-yellow' : 'bg-secondary-lt text-secondary' }}">
                                            {{ $branchRule ? $numberingDocument['branch_preview'] : 'Belum override' }}
                                        </span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Prefix</label>
                                            <input type="text"
                                                   name="branch_numbering[{{ $numberingDocument['type'] }}][prefix]"
                                                   class="form-control"
                                                   value="{{ old('branch_numbering.'.$numberingDocument['type'].'.prefix', optional($branchRule)->prefix ?: $definition['default_prefix']) }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Format Nomor</label>
                                            <input type="text"
                                                   name="branch_numbering[{{ $numberingDocument['type'] }}][number_format]"
                                                   class="form-control"
                                                   value="{{ old('branch_numbering.'.$numberingDocument['type'].'.number_format', optional($branchRule)->number_format ?: $definition['default_format']) }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Padding</label>
                                            <input type="number"
                                                   name="branch_numbering[{{ $numberingDocument['type'] }}][padding]"
                                                   class="form-control"
                                                   min="1" max="12"
                                                   value="{{ old('branch_numbering.'.$numberingDocument['type'].'.padding', optional($branchRule)->padding ?: 5) }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">No. Berikutnya</label>
                                            <input type="number"
                                                   name="branch_numbering[{{ $numberingDocument['type'] }}][next_number]"
                                                   class="form-control"
                                                   min="1"
                                                   value="{{ old('branch_numbering.'.$numberingDocument['type'].'.next_number', optional($branchRule)->next_number ?: 1) }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Reset Counter</label>
                                            <select name="branch_numbering[{{ $numberingDocument['type'] }}][reset_period]" class="form-select">
                                                <option value="never" @selected(old('branch_numbering.'.$numberingDocument['type'].'.reset_period', optional($branchRule)->reset_period ?: 'never') === 'never')>Tidak pernah</option>
                                                <option value="monthly" @selected(old('branch_numbering.'.$numberingDocument['type'].'.reset_period', optional($branchRule)->reset_period) === 'monthly')>Reset setiap bulan</option>
                                                <option value="yearly" @selected(old('branch_numbering.'.$numberingDocument['type'].'.reset_period', optional($branchRule)->reset_period) === 'yearly')>Reset setiap tahun</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            <div class="col-12"><hr class="my-1"></div>

                            @foreach($documentPreview['numbering_documents'] as $numberingDocument)
                                @php($branchWorkflowRule = $numberingDocument['branch_workflow_rule'])
                                <div class="col-12 border rounded-3 p-3">
                                    <div class="fw-semibold mb-1">Workflow {{ $numberingDocument['label'] }}</div>
                                    <div class="text-muted small mb-3">Override branch untuk rule approval dokumen ini.</div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-check">
                                                <input type="hidden" name="branch_workflow[{{ $numberingDocument['type'] }}][requires_approval_before_conversion]" value="0">
                                                <input class="form-check-input" type="checkbox" name="branch_workflow[{{ $numberingDocument['type'] }}][requires_approval_before_conversion]" value="1" @checked(old('branch_workflow.'.$numberingDocument['type'].'.requires_approval_before_conversion', optional($branchWorkflowRule)->requires_approval_before_conversion ?? $numberingDocument['requires_approval_before_conversion']))>
                                                <span class="form-check-label">Wajib approval sebelum convert</span>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-check">
                                                <input type="hidden" name="branch_workflow[{{ $numberingDocument['type'] }}][requires_approval_before_finalize]" value="0">
                                                <input class="form-check-input" type="checkbox" name="branch_workflow[{{ $numberingDocument['type'] }}][requires_approval_before_finalize]" value="1" @checked(old('branch_workflow.'.$numberingDocument['type'].'.requires_approval_before_finalize', optional($branchWorkflowRule)->requires_approval_before_finalize ?? false))>
                                                <span class="form-check-label">Wajib approval sebelum finalize/post</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            <div class="col-12"><hr class="my-1"></div>

                            <div class="col-12">
                                <label class="form-label">Header Dokumen</label>
                                <textarea name="branch_document_header" class="form-control" rows="3" placeholder="Nama outlet, alamat cabang, dll.">{{ old('branch_document_header', optional($branchDocumentSetting)->document_header) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Footer Dokumen</label>
                                <textarea name="branch_document_footer" class="form-control" rows="2" placeholder="Pesan khusus cabang ini.">{{ old('branch_document_footer', optional($branchDocumentSetting)->document_footer) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Footer Struk</label>
                                <textarea name="branch_receipt_footer" class="form-control" rows="2" placeholder="Pesan untuk struk kasir cabang.">{{ old('branch_receipt_footer', optional($branchDocumentSetting)->receipt_footer) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan Internal</label>
                                <textarea name="branch_notes" class="form-control" rows="2" placeholder="Catatan internal cabang.">{{ old('branch_notes', optional($branchDocumentSetting)->notes) }}</textarea>
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
