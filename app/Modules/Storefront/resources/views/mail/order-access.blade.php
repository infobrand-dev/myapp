<p>Halo {{ $sale->customer_name_snapshot ?: 'customer' }},</p>

<p>Pembayaran untuk order <strong>{{ $sale->sale_number }}</strong> sudah kami terima.</p>

<p>
    Buka detail order dan akses delivery instruction Anda di link berikut:
    <br>
    <a href="{{ $orderUrl }}">{{ $orderUrl }}</a>
</p>

<p>Ringkasan item:</p>
<ul>
    @foreach($sale->items as $item)
        <li>{{ $item->product_name_snapshot }} x {{ number_format((float) $item->qty, 0, ',', '.') }}</li>
    @endforeach
</ul>

<p>Terima kasih.</p>
