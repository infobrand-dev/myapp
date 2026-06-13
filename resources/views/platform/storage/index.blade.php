@extends('layouts.platform')

@section('title', 'Storage Profiles')

@section('content')
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="text-secondary text-uppercase fw-bold small">Platform Owner</div>
            <h1 class="page-title mb-1">Storage Profiles</h1>
            <div class="text-muted">Owner cukup menyiapkan endpoint dan kredensial storage. Routing upload, fallback, dan audit akses file dikelola otomatis oleh sistem.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">Dashboard</a>
            <a href="{{ route('platform.golive') }}" class="btn btn-outline-secondary">Go-Live Audit</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Tambah Storage Profile</h3>
                </div>
                <form method="POST" action="{{ route('platform.storage.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Code</label>
                                <input type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" required>
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama</label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Driver</label>
                                <select name="driver" class="form-select">
                                    <option value="s3" @selected(old('driver', 's3') === 's3')>S3</option>
                                    <option value="local" @selected(old('driver') === 'local')>Local Fallback</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Scope</label>
                                <select name="visibility_scope" class="form-select">
                                    <option value="private" @selected(old('visibility_scope', 'private') === 'private')>Private</option>
                                    <option value="public" @selected(old('visibility_scope') === 'public')>Public</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bucket</label>
                                <input type="text" name="bucket" value="{{ old('bucket') }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Region</label>
                                <input type="text" name="region" value="{{ old('region') }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Endpoint</label>
                                <input type="text" name="endpoint" value="{{ old('endpoint') }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Public URL</label>
                                <input type="text" name="url" value="{{ old('url') }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Root Path</label>
                                <input type="text" name="root_path" value="{{ old('root_path') }}" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Weight</label>
                                <input type="number" name="weight" value="{{ old('weight', 100) }}" min="1" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Priority</label>
                                <input type="number" name="priority" value="{{ old('priority', 100) }}" min="1" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Purposes</label>
                                <input type="text" name="purposes" value="{{ old('purposes') }}" class="form-control" placeholder="public_asset, payment_proof">
                                <div class="form-hint">Kosongkan jika profile ini boleh dipakai untuk semua purpose pada scope yang sama.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Access Key ID</label>
                                <input type="text" name="access_key_id" value="{{ old('access_key_id') }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Secret Access Key</label>
                                <textarea name="secret_access_key" rows="3" class="form-control">{{ old('secret_access_key') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active" value="1" @checked(old('is_active', true))>
                                    <label class="form-check-label" for="isActive">Active</label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="isDefault" name="is_default" value="1" @checked(old('is_default'))>
                                    <label class="form-check-label" for="isDefault">Default untuk scope ini</label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="pathStyle" name="use_path_style_endpoint" value="1" @checked(old('use_path_style_endpoint'))>
                                    <label class="form-check-label" for="pathStyle">Use path style endpoint</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Simpan Profile</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Profile Terdaftar</h3>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    @forelse($profiles as $profile)
                        <div class="border rounded-3 p-3">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <div class="d-flex gap-2 flex-wrap align-items-center">
                                        <span class="fw-semibold">{{ $profile->name }}</span>
                                        <span class="badge {{ $profile->is_active ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">{{ $profile->is_active ? 'ACTIVE' : 'INACTIVE' }}</span>
                                        @if($profile->is_default)
                                            <span class="badge bg-primary-lt text-primary">DEFAULT</span>
                                        @endif
                                        <span class="badge bg-secondary-lt text-secondary">{{ strtoupper($profile->driver) }}</span>
                                        <span class="badge bg-secondary-lt text-secondary">{{ strtoupper($profile->visibility_scope) }}</span>
                                    </div>
                                    <div class="text-muted small mt-1">
                                        <code>{{ $profile->code }}</code> • files: {{ $profile->stored_files_count }} • weight {{ $profile->weight }} • priority {{ $profile->priority }}
                                    </div>
                                    @if($profile->last_error_summary)
                                        <div class="text-danger small mt-2">{{ $profile->last_error_summary }}</div>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('platform.storage.toggle', $profile) }}">
                                    @csrf
                                    <input type="hidden" name="is_active" value="{{ $profile->is_active ? 0 : 1 }}">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">{{ $profile->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </div>

                            <form method="POST" action="{{ route('platform.storage.update', $profile) }}">
                                @csrf
                                @method('PUT')
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nama</label>
                                        <input type="text" name="name" value="{{ old('name.' . $profile->id, $profile->name) }}" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Code</label>
                                        <input type="text" name="code" value="{{ old('code.' . $profile->id, $profile->code) }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Bucket</label>
                                        <input type="text" name="bucket" value="{{ old('bucket.' . $profile->id, $profile->bucket) }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Region</label>
                                        <input type="text" name="region" value="{{ old('region.' . $profile->id, $profile->region) }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Endpoint</label>
                                        <input type="text" name="endpoint" value="{{ old('endpoint.' . $profile->id, $profile->endpoint) }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Public URL</label>
                                        <input type="text" name="url" value="{{ old('url.' . $profile->id, $profile->url) }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Root Path</label>
                                        <input type="text" name="root_path" value="{{ old('root_path.' . $profile->id, $profile->root_path) }}" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Weight</label>
                                        <input type="number" name="weight" value="{{ old('weight.' . $profile->id, $profile->weight) }}" min="1" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Priority</label>
                                        <input type="number" name="priority" value="{{ old('priority.' . $profile->id, $profile->priority) }}" min="1" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Purposes</label>
                                        <input type="text" name="purposes" value="{{ old('purposes.' . $profile->id, implode(', ', $profile->purposes ?? [])) }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Access Key ID</label>
                                        <input type="text" name="access_key_id" class="form-control" placeholder="Kosongkan untuk tetap pakai nilai sekarang">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Secret Access Key</label>
                                        <textarea name="secret_access_key" rows="2" class="form-control" placeholder="Kosongkan untuk tetap pakai nilai sekarang"></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Driver</label>
                                        <select name="driver" class="form-select">
                                            <option value="s3" @selected($profile->driver === 's3')>S3</option>
                                            <option value="local" @selected($profile->driver === 'local')>Local</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Scope</label>
                                        <select name="visibility_scope" class="form-select">
                                            <option value="private" @selected($profile->visibility_scope === 'private')>Private</option>
                                            <option value="public" @selected($profile->visibility_scope === 'public')>Public</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex flex-column justify-content-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="default-{{ $profile->id }}" name="is_default" value="1" @checked($profile->is_default)>
                                            <label class="form-check-label" for="default-{{ $profile->id }}">Default scope</label>
                                        </div>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="pathstyle-{{ $profile->id }}" name="use_path_style_endpoint" value="1" @checked($profile->use_path_style_endpoint)>
                                            <label class="form-check-label" for="pathstyle-{{ $profile->id }}">Path style endpoint</label>
                                        </div>
                                        <input type="hidden" name="is_active" value="{{ $profile->is_active ? 1 : 0 }}">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="text-muted">Belum ada storage profile. Sistem masih akan fallback ke disk Laravel yang lama sampai owner mengisi profile di sini.</div>
                    @endforelse
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">At-Risk Files</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category</th>
                                <th>Profile</th>
                                <th>Status</th>
                                <th>Path</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($atRiskFiles as $file)
                                <tr>
                                    <td>{{ $file->id }}</td>
                                    <td>{{ $file->category }}</td>
                                    <td>{{ optional($file->storageProfile)->code ?? '-' }}</td>
                                    <td><span class="badge {{ $file->availability_status === 'available' ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning' }}">{{ strtoupper($file->availability_status) }}</span></td>
                                    <td><code>{{ $file->path }}</code></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">Belum ada file yang terdeteksi bermasalah atau terkait profile nonaktif.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

