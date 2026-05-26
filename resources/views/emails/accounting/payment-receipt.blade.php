@extends('emails.layout')

@section('subject', 'Tanda Terima Pembayaran ' . $paymentNumber)

@section('content')
    @php($money = app(\App\Support\MoneyFormatter::class))
    <h1>Tanda Terima Pembayaran</h1>
    <p>Halo {{ $customerName }}, pembayaran Anda sudah kami terima.</p>
    <div class="info-box">
        Payment: {{ $paymentNumber }}<br>
        Tanggal bayar: {{ $paidAt ?: '-' }}<br>
        Metode: {{ $paymentMethod ?: '-' }}<br>
        Referensi: {{ $referenceNumber ?: '-' }}<br>
        Nominal: {{ $money->format($amount, $currencyCode) }}
    </div>
    @if(!empty($sales))
        <p>Dialokasikan ke dokumen berikut:</p>
        <ul>
            @foreach($sales as $sale)
                <li>{{ $sale['sale_number'] }} - {{ $money->format($sale['grand_total'], $currencyCode) }}</li>
            @endforeach
        </ul>
    @endif
    @if($notes)
        <p>{{ $notes }}</p>
    @endif
@endsection
