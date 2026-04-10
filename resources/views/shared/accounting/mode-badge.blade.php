@php
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
@endphp
<span class="badge {{ $isAdvancedMode ? 'bg-blue-lt text-blue' : 'bg-secondary-lt text-secondary' }}">
    {{ $isAdvancedMode ? 'Advanced mode' : 'Standard mode' }}
</span>
