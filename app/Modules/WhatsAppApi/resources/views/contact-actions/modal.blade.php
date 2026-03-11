@php
    $config = [
        'instances' => $instances,
        'templates' => $templates,
        'defaults' => [
            'returnTo' => url()->full(),
        ],
    ];
@endphp

<style>
    .wa-contact-preview-shell { background: transparent; }
    .wa-phone { border-radius: 1rem; overflow: hidden; border: 1px solid #d3d8dd; box-shadow: 0 .5rem 1rem rgba(0,0,0,.08); background: #fff; }
    .wa-head { background: #1f2c34; color: #e9edef; padding: .65rem .8rem; display: flex; align-items: center; gap: .55rem; }
    .wa-av { width: 1.9rem; height: 1.9rem; border-radius: 50%; background: #3a4a55; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .75rem; }
    .wa-body { min-height: 24rem; padding: 1rem; background: #efeae2; background-image: radial-gradient(rgba(17,27,33,.05) 1px, transparent 1px); background-size: 12px 12px; }
    .wa-bubble { background: #fff; border-radius: .85rem; border-top-left-radius: .35rem; padding: .7rem .75rem .55rem; box-shadow: 0 .15rem .45rem rgba(17,27,33,.08); }
    .wa-bubble-header { font-weight: 700; margin-bottom: .35rem; color: #1f2c34; }
    .wa-bubble-body { line-height: 1.38; color: #111b21; white-space: pre-wrap; word-break: break-word; }
    .wa-bubble-footer { margin-top: .45rem; font-size: .78rem; color: #667781; }
    .wa-media { border: 1px dashed #c8ccd0; border-radius: .5rem; font-size: .78rem; color: #5b6670; background: #f7f8f8; padding: .45rem .55rem; margin-bottom: .45rem; word-break: break-all; }
    .wa-btns { margin-top: .6rem; display: flex; flex-direction: column; gap: .35rem; }
    .wa-btn { border: 1px solid #d6dadd; background: #f5f6f6; border-radius: .55rem; padding: .4rem .55rem; font-size: .8rem; display: flex; justify-content: space-between; color: #0f6f5c; font-weight: 600; }
    .wa-btn small { color: #667781; margin-left: .45rem; font-weight: 500; text-transform: uppercase; }
</style>

<div class="modal modal-blur fade" id="wa-contact-action-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('whatsapp-api.contact-actions.send-template') }}" id="wa-contact-action-form">
                @csrf
                <input type="hidden" name="contact_id" id="wa-contact-id">
                <input type="hidden" name="return_to" id="wa-contact-return-to" value="{{ url()->full() }}">
                <div class="modal-header">
                    <div>
                        <h3 class="modal-title mb-0">Kirim Template WhatsApp</h3>
                        <div class="text-muted small" id="wa-contact-action-subtitle">Pilih contact terlebih dahulu.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="card border-0 bg-body-tertiary">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Instance</label>
                                            <select class="form-select" name="instance_id" id="wa-contact-instance" required>
                                                <option value="">Pilih instance</option>
                                                @foreach($instances as $instance)
                                                    <option
                                                        value="{{ $instance['id'] }}"
                                                        data-provider="{{ $instance['provider'] }}"
                                                        data-namespace="{{ $instance['namespace'] }}">
                                                        {{ $instance['name'] }}@if($instance['provider']) ({{ strtoupper($instance['provider']) }}) @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Template</label>
                                            <select class="form-select" name="template_id" id="wa-contact-template" required>
                                                <option value="">Pilih template</option>
                                            </select>
                                            <div class="form-hint" id="wa-contact-template-hint">Template disaring mengikuti namespace instance jika tersedia.</div>
                                        </div>
                                        <div class="col-12">
                                            <div class="text-uppercase text-muted small fw-bold mb-2">Variables</div>
                                            <div class="text-muted small mb-2">Nilai awal mengikuti mapping template. Anda masih bisa override sebelum kirim.</div>
                                            <div id="wa-contact-variables-empty" class="text-muted small">Template ini tidak membutuhkan variable.</div>
                                            <div id="wa-contact-variables" class="d-grid gap-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card border-0 wa-contact-preview-shell">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                        <div>
                                            <div class="fw-semibold" id="wa-preview-contact-name">Preview</div>
                                            <div class="text-muted small" id="wa-preview-contact-phone">-</div>
                                        </div>
                                        <span class="badge bg-success-lt text-success" id="wa-preview-template-name">Template</span>
                                    </div>
                                    <div class="wa-phone">
                                        <div class="wa-head">
                                            <div class="wa-av">WA</div>
                                            <div>
                                                <div class="fw-semibold" id="wa-preview-header-contact">Contact</div>
                                                <div class="text-muted small">Live preview template</div>
                                            </div>
                                        </div>
                                        <div class="wa-body">
                                            <div class="wa-bubble">
                                                <div class="wa-media" id="wa-preview-media" style="display:none;"></div>
                                                <div class="wa-bubble-header" id="wa-preview-header" style="display:none;"></div>
                                                <div class="wa-bubble-body" id="wa-preview-body">Pilih template untuk melihat preview.</div>
                                                <div class="wa-bubble-footer" id="wa-preview-footer" style="display:none;"></div>
                                                <div class="wa-btns" id="wa-preview-buttons" style="display:none;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" @if(empty($instances) || empty($templates)) disabled @endif>Kirim Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="application/json" id="wa-contact-action-config">@json($config)</script>

@push('scripts')
    <script src="{{ mix('js/modules/whatsapp-api/contact-actions.js') }}" defer></script>
@endpush
