@php
    $type = $type ?? 'commerce';
    $value = (string) ($value ?? '');

    $config = match ($type) {
        'payment' => match ($value) {
            'paid' => ['label' => 'Paid', 'class' => 'bg-green-lt text-green'],
            'posted' => ['label' => 'Posted', 'class' => 'bg-green-lt text-green'],
            'unpaid' => ['label' => 'Unpaid', 'class' => 'bg-yellow-lt text-yellow'],
            'partial' => ['label' => 'Partial', 'class' => 'bg-orange-lt text-orange'],
            'void' => ['label' => 'Void', 'class' => 'bg-red-lt text-red'],
            default => ['label' => $value !== '' ? strtoupper(str_replace('_', ' ', $value)) : '-', 'class' => 'bg-secondary-lt text-secondary'],
        },
        'payment_state' => match ($value) {
            'paid' => ['label' => 'Pembayaran diterima', 'class' => 'bg-green-lt text-green'],
            'checkout_created' => ['label' => 'Checkout dibuat', 'class' => 'bg-blue-lt text-blue'],
            'pending' => ['label' => 'Menunggu pembayaran', 'class' => 'bg-yellow-lt text-yellow'],
            'failed' => ['label' => 'Pembayaran gagal', 'class' => 'bg-red-lt text-red'],
            'expired' => ['label' => 'Expired', 'class' => 'bg-orange-lt text-orange'],
            'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-secondary-lt text-secondary'],
            default => ['label' => $value !== '' ? strtoupper(str_replace('_', ' ', $value)) : '-', 'class' => 'bg-secondary-lt text-secondary'],
        },
        'fulfillment' => match ($value) {
            'packing' => ['label' => 'Packing', 'class' => 'bg-orange-lt text-orange'],
            'ready' => ['label' => 'Ready', 'class' => 'bg-green-lt text-green'],
            'pending' => ['label' => 'Pending', 'class' => 'bg-yellow-lt text-yellow'],
            default => ['label' => $value !== '' ? strtoupper(str_replace('_', ' ', $value)) : '-', 'class' => 'bg-secondary-lt text-secondary'],
        },
        'shipping' => match ($value) {
            'ready' => ['label' => 'Ready', 'class' => 'bg-blue-lt text-blue'],
            'pending' => ['label' => 'Pending', 'class' => 'bg-yellow-lt text-yellow'],
            'shipped' => ['label' => 'Shipped', 'class' => 'bg-green-lt text-green'],
            default => ['label' => $value !== '' ? strtoupper(str_replace('_', ' ', $value)) : '-', 'class' => 'bg-secondary-lt text-secondary'],
        },
        default => match ($value) {
            'pending_payment' => ['label' => 'Menunggu pembayaran', 'class' => 'bg-yellow-lt text-yellow'],
            'paid' => ['label' => 'Paid', 'class' => 'bg-green-lt text-green'],
            'ready_for_fulfillment' => ['label' => 'Siap diproses', 'class' => 'bg-blue-lt text-blue'],
            'expired' => ['label' => 'Expired', 'class' => 'bg-orange-lt text-orange'],
            'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-secondary-lt text-secondary'],
            default => ['label' => $value !== '' ? strtoupper(str_replace('_', ' ', $value)) : '-', 'class' => 'bg-secondary-lt text-secondary'],
        },
    };
@endphp

<span class="badge {{ $config['class'] }}">{{ $config['label'] }}</span>
