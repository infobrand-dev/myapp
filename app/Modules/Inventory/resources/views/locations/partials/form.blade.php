@csrf
@if(($method ?? 'POST') !== 'POST')
    @method($method)
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Location Form</h3>
            </div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $location->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $location->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $location->type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Parent Location</label>
                    <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                        <option value="">No parent</option>
                        @foreach($parentOptions as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_id', $location->parent_id) === (string) $parent->id)>{{ $parent->name }} ({{ $parent->code }})</option>
                        @endforeach
                    </select>
                    @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-check">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" name="is_default" value="1" class="form-check-input" @checked((bool) old('is_default', $location->is_default))>
                        <span class="form-check-label">Set as default location</span>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked((bool) old('is_active', $location->is_active ?? true))>
                        <span class="form-check-label">Active</span>
                    </label>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('inventory.locations.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </div>
</div>
