<div class="col-md-6">
    <label class="form-label">Code</label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $method->code) }}" @disabled($method->is_system)>
</div>
<div class="col-md-6">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $method->name) }}" required>
</div>
<div class="col-md-6">
    <label class="form-label">Type</label>
    <select name="type" class="form-select" required>
        @foreach($typeOptions as $value => $label)
            <option value="{{ $value }}" @selected(old('type', $method->type ?: 'manual') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6">
    <label class="form-label">Sort Order</label>
    <input type="number" name="sort_order" min="0" class="form-control" value="{{ old('sort_order', $method->sort_order ?? 0) }}">
</div>
<div class="col-md-6">
    <label class="form-check mt-4">
        <input type="checkbox" name="requires_reference" value="1" class="form-check-input" @checked(old('requires_reference', $method->requires_reference))>
        <span class="form-check-label">Requires reference</span>
    </label>
</div>
<div class="col-md-6">
    <label class="form-check mt-4">
        <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $method->exists ? $method->is_active : true))>
        <span class="form-check-label">Active</span>
    </label>
</div>
