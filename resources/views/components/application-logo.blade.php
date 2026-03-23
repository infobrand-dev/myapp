<span {{ $attributes->merge(['class' => 'auth-app-icon']) }}>
    {{ strtoupper(substr(config('app.name', 'A'), 0, 1)) }}
</span>
