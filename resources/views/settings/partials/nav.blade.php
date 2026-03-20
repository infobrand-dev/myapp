<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">Settings Menu</h3>
    </div>
    <div class="list-group list-group-flush">
        @foreach($sections as $key => $item)
            <a href="{{ route($item['route']) }}" class="list-group-item list-group-item-action d-flex align-items-start gap-3 {{ $currentSection === $key ? 'active' : '' }}">
                <span class="fs-3 lh-1"><i class="{{ $item['icon'] }}"></i></span>
                <span>
                    <span class="d-block fw-semibold">{{ $item['label'] }}</span>
                    <span class="d-block small {{ $currentSection === $key ? 'text-white-50' : 'text-muted' }}">{{ $item['description'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</div>
