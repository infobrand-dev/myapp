<div class="row g-3">
    <div class="col-12">
        <form method="POST" action="{{ route('settings.general.save') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Profil Workspace</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Workspace <span class="text-danger">*</span></label>
                            <input type="text" name="workspace_name"
                                   class="form-control @error('workspace_name') is-invalid @enderror"
                                   value="{{ old('workspace_name', optional($tenant)->name ?? '') }}" required>
                            @error('workspace_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Alamat Workspace</label>
                            <input type="text" class="form-control bg-body-secondary"
                                   value="{{ optional($tenant)->slug ?? '-' }}" readonly>
                            <div class="form-hint">Tidak dapat diubah setelah workspace dibuat.</div>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-12">
                            <div class="fw-semibold mb-1">Branding Halaman Publik</div>
                            <div class="text-muted small">Digunakan untuk halaman publik tenant seperti home storefront.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Storefront Publik</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="publicStorefrontEnabled"
                                       name="public_storefront_enabled" value="1"
                                       @checked(old('public_storefront_enabled', data_get($tenant, 'meta.public_storefront_enabled', true)))>
                                <label class="form-check-label" for="publicStorefrontEnabled">Aktifkan home dan katalog publik tenant</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Company Publik Default</label>
                            <select name="default_public_company_id" class="form-select @error('default_public_company_id') is-invalid @enderror">
                                <option value="">Ikuti company aktif pertama</option>
                                @foreach(($companies ?? collect()) as $companyOption)
                                    <option value="{{ $companyOption->id }}"
                                            @selected((string) old('default_public_company_id', data_get($tenant, 'meta.default_public_company_id', '')) === (string) $companyOption->id)>
                                        {{ $companyOption->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('default_public_company_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-hint">Dipakai untuk storefront publik, payment gateway, dan shipping provider saat visitor belum login.</div>
                            @if(!data_get($tenant, 'meta.default_public_company_id'))
                                <div class="form-hint text-warning">Sebaiknya pilih company publik default agar storefront tidak bergantung pada fallback company aktif pertama.</div>
                            @endif
                        </div>

                        @if($currentCompany)
                            <div class="col-12"><hr class="my-1"></div>

                            <div class="col-12">
                                <div class="fw-semibold mb-1">Origin Pengiriman Company Aktif</div>
                                <div class="text-muted small">Dipakai saat storefront menghitung estimasi ongkir untuk pesanan delivery.</div>
                            </div>

                            <div class="col-12">
                                @php
                                    $originPostal = old('company_shipping_origin_postal_code', data_get($currentCompany, 'meta.shipping_origin_postal_code', data_get($currentCompany, 'meta.postal_code', '')));
                                    $originArea = old('company_shipping_origin_area_id', data_get($currentCompany, 'meta.shipping_origin_area_id', ''));
                                    $originReady = filled($originPostal) || filled($originArea);
                                @endphp
                                <div class="alert {{ $originReady ? 'alert-success' : 'alert-warning' }} py-2 mb-0">
                                    {{ $originReady
                                        ? 'Origin pengiriman sudah terisi. Checkout delivery bisa melanjutkan quote sesuai provider yang aktif.'
                                        : 'Origin pengiriman belum lengkap. Checkout delivery akan gagal sampai postal code atau area ID company aktif diisi.' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Origin Postal Code</label>
                                <input type="text" name="company_shipping_origin_postal_code"
                                       class="form-control @error('company_shipping_origin_postal_code') is-invalid @enderror"
                                       value="{{ old('company_shipping_origin_postal_code', data_get($currentCompany, 'meta.shipping_origin_postal_code', data_get($currentCompany, 'meta.postal_code', ''))) }}"
                                       placeholder="Contoh: 40123">
                                @error('company_shipping_origin_postal_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Dipakai oleh provider seperti Biteship.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Origin Area ID</label>
                                <input type="text" name="company_shipping_origin_area_id"
                                       class="form-control @error('company_shipping_origin_area_id') is-invalid @enderror"
                                       value="{{ old('company_shipping_origin_area_id', data_get($currentCompany, 'meta.shipping_origin_area_id', '')) }}"
                                       placeholder="Contoh: 501">
                                @error('company_shipping_origin_area_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Dipakai oleh provider seperti RajaOngkir.</div>
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label">Nama Publik</label>
                            <input type="text" name="public_brand_name"
                                   class="form-control @error('public_brand_name') is-invalid @enderror"
                                   value="{{ old('public_brand_name', data_get($tenant, 'meta.public_brand_name', optional($tenant)->name ?? '')) }}"
                                   placeholder="Contoh: Acme Studio">
                            @error('public_brand_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Logo Publik</label>
                            <input type="file" name="public_brand_logo"
                                   class="form-control @error('public_brand_logo') is-invalid @enderror"
                                   accept=".jpg,.jpeg,.png,.webp,.svg">
                            @error('public_brand_logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-hint">Format: JPG, PNG, WEBP, atau SVG. Maksimal 2 MB.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Deskripsi Publik</label>
                            <textarea name="public_brand_description"
                                      class="form-control @error('public_brand_description') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Tulis deskripsi singkat yang tampil untuk visitor di halaman publik tenant.">{{ old('public_brand_description', data_get($tenant, 'meta.public_brand_description', '')) }}</textarea>
                            @error('public_brand_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if(data_get($tenant, 'meta.public_brand_logo_path'))
                            <div class="col-12">
                                <label class="form-label">Logo Saat Ini</label>
                                <div class="border rounded-3 p-3 d-inline-flex align-items-center justify-content-center bg-body-secondary">
                                    <img src="{{ app(\App\Services\StorageAccessService::class)->publicUrlFromPath(data_get($tenant, 'meta.public_brand_logo_path'), 'public') }}"
                                         alt="Logo {{ data_get($tenant, 'meta.public_brand_name', optional($tenant)->name ?? config('app.name')) }}"
                                         style="max-height: 64px; width: auto; display: block;">
                                </div>
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="mt-1">
                                @if(optional($tenant)->is_active ?? true)
                                    <span class="badge bg-green-lt text-green">Aktif</span>
                                @else
                                    <span class="badge bg-red-lt text-red">Nonaktif</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Paket Aktif</label>
                            <div class="fw-semibold mt-1">
                                {{ optional($plan)->display_name ?? optional($plan)->name ?? '-' }}
                            </div>
                            @if(!optional($plan)->name)
                                <div class="form-hint">Belum ada paket terpasang.</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Mata Uang Workspace</label>
                            <input type="text" class="form-control bg-body-secondary"
                                   value="{{ $currencyOptions[$defaultCurrency] ?? $defaultCurrency }}" readonly>
                        </div>

                        @if($currentCompany)
                            <div class="col-md-6">
                                <label class="form-label">Mata Uang Company Aktif</label>
                                <input type="text" class="form-control bg-body-secondary"
                                       value="{{ $currencyOptions[$companyDefaultCurrency] ?? ($companyDefaultCurrency ?: $defaultCurrency) }}" readonly>
                            </div>
                        @endif

                        <div class="col-12">
                            <div class="alert alert-info mb-0 py-2">
                                <div class="d-flex gap-2">
                                    <i class="ti ti-info-circle flex-shrink-0 mt-1"></i>
                                    <div>Mata uang ditampilkan sebagai referensi dan tidak dapat diubah dari halaman ini.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
