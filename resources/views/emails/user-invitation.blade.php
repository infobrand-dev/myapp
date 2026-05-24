<p>Halo{{ $invitation->name ? ' ' . e($invitation->name) : '' }},</p>

<p>Anda diundang ke workspace <strong>{{ $tenant->name }}</strong>.</p>

<p>Gunakan tautan berikut untuk mengaktifkan akun dan membuat password:</p>

<p><a href="{{ $acceptUrl }}">{{ $acceptUrl }}</a></p>

<p>Tautan ini berlaku sampai {{ optional($invitation->expires_at)->format('d M Y H:i') ?: '-' }}.</p>
