{{--
    Shared product autocomplete field for use inside item rows.

    Required variables (set with @include or parent context):
      $index        — numeric row index, e.g. 0
      $keyName      — hidden input name, e.g. 'items[0][purchasable_key]' or 'items[0][sellable_key]'
      $selectedKey  — current value of the key, e.g. 'product:5'
      $selectedLabel — display label for pre-filled rows, e.g. 'Product Name'
      $selectedDescription — hint text for pre-filled rows
--}}
<div class="position-relative">
    <input type="text"
        class="form-control"
        placeholder="Search by name or SKU…"
        value="{{ $selectedLabel ?? '' }}"
        autocomplete="off"
        data-item-search>
    <input type="hidden"
        name="{{ $keyName }}"
        value="{{ $selectedKey ?? '' }}"
        data-item-key>
    <div class="position-absolute w-100 border rounded bg-white shadow-sm d-none"
        style="top:calc(100% + 2px); z-index:1050; max-height:240px; overflow-y:auto;"
        data-item-dropdown></div>
</div>
<div class="form-hint" data-item-hint>
    {{ $selectedDescription ?? 'Type to search — default price will be filled automatically.' }}
</div>
