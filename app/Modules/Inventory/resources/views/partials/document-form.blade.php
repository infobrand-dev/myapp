@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $submitRoute }}">
    @csrf
    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ $title }}</h3></div>
                <div class="card-body row g-3">
                    @foreach($metaFields as $field)
                        <div class="{{ $field['column'] ?? 'col-12' }}">
                            @include('shared.accounting.field-label', [
                                'label' => $field['label'],
                                'tooltip' => $field['tooltip'] ?? '',
                                'required' => (bool) ($field['required'] ?? false),
                            ])
                            @if(($field['type'] ?? 'text') === 'select')
                                <select name="{{ $field['name'] }}" class="form-select">
                                    @foreach($field['options'] as $value => $label)
                                        <option value="{{ $value }}" @selected((string) old($field['name'], $field['value'] ?? '') === (string) $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @elseif(($field['type'] ?? 'text') === 'textarea')
                                <textarea name="{{ $field['name'] }}" class="form-control" rows="4">{{ old($field['name'], $field['value'] ?? '') }}</textarea>
                            @else
                                <input type="{{ $field['type'] ?? 'text' }}" name="{{ $field['name'] }}" class="form-control" value="{{ old($field['name'], $field['value'] ?? '') }}">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Items</h3>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-item-row">Tambah Item</button>
                </div>
                <div class="card-body" id="item-rows">
                    <div class="row g-2 align-items-end item-row mb-2">
                        <div class="col-md-4">
                            @include('shared.accounting.field-label', [
                                'label' => 'Product',
                                'tooltip' => 'Pilih produk yang stoknya akan diproses pada dokumen inventory ini.',
                                'required' => true,
                            ])
                            <select name="items[0][product_id]" class="form-select">
                                <option value="">Pilih produk</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                @endforeach
                            </select>
                        </div>
                        @foreach($itemFields as $field)
                            <div class="{{ $field['column'] ?? 'col-md-2' }}">
                                @include('shared.accounting.field-label', [
                                    'label' => $field['label'],
                                    'tooltip' => $field['tooltip'] ?? '',
                                    'required' => (bool) ($field['required'] ?? false),
                                ])
                                @if(($field['type'] ?? 'text') === 'select')
                                    <select name="items[0][{{ $field['name'] }}]" class="form-select">
                                        @foreach($field['options'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="{{ $field['type'] ?? 'text' }}" name="items[0][{{ $field['name'] }}]" class="form-control" value="{{ $field['value'] ?? '' }}">
                                @endif
                            </div>
                        @endforeach
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger w-100 remove-item-row">X</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ $cancelRoute }}" class="btn btn-outline-secondary">Batal</a>
                <button class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
(() => {
    const container = document.getElementById('item-rows');
    const addButton = document.getElementById('add-item-row');

    const reindex = () => {
        container.querySelectorAll('.item-row').forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`);
            });
        });
    };

    addButton?.addEventListener('click', () => {
        const clone = container.querySelector('.item-row')?.cloneNode(true);
        if (!clone) return;
        clone.querySelectorAll('input').forEach((field) => field.value = field.type === 'number' ? (field.defaultValue || '') : '');
        clone.querySelectorAll('select').forEach((field) => field.selectedIndex = 0);
        container.appendChild(clone);
        reindex();
    });

    container?.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-item-row');
        if (!button) return;
        if (container.querySelectorAll('.item-row').length === 1) return;
        button.closest('.item-row')?.remove();
        reindex();
    });
})();
</script>
@endpush
