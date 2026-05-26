@extends('emails.layout')

@section('subject', 'Pengingat Pembayaran ' . $documentNumber)

@section('content')
    @php($money = app(\App\Support\MoneyFormatter::class))
    <h1>Pengingat Pembayaran</h1>
    <p>Halo {{ $customerName }}, kami ingin mengingatkan tagihan berikut masih memiliki sisa pembayaran.</p>
    <div class="info-box">
        Invoice: {{ $documentNumber }}<br>
        Jatuh tempo: {{ $dueDate ?: '-' }}<br>
        Total invoice: {{ $money->format($grandTotal, $currencyCode) }}<br>
        Sudah dibayar: {{ $money->format($paidTotal, $currencyCode) }}<br>
        Sisa tagihan: {{ $money->format($balanceDue, $currencyCode) }}
    </div>
    @if($notes)
        <p>{{ $notes }}</p>
    @endif
@endsection
