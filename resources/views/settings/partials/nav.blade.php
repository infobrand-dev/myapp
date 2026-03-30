<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">Settings Menu</h3>
    </div>
    <div class="list-group list-group-flush settings-nav">
        @foreach($sections as $key => $item)
            <a href="{{ route($item['route']) }}"
               class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 {{ $currentSection === $key ? 'active' : '' }}">
                <span class="nav-item-icon fs-4 lh-1 flex-shrink-0">
                    <i class="{{ $item['icon'] }}"></i>
                </span>
                <span class="min-width-0">
                    <span class="d-block fw-semibold" style="font-size:.875rem;">{{ $item['label'] }}</span>
                    <span class="d-block nav-item-desc">{{ $item['description'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</div>
