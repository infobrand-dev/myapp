@extends('emails.layout')

@section('subject', 'Invoice ' . $documentNumber)

@section('content')
    @php($money = app(\App\Support\MoneyFormatter::class))
    <h1>Invoice {{ $documentNumber }}</h1>
    <p>Halo {{ $customerName }}, berikut invoice/tagihan Anda.</p>
    <div class="info-box">
        Tanggal: {{ $documentDate ?: '-' }}<br>
        Jatuh tempo: {{ $dueDate ?: '-' }}<br>
        Total: {{ $money->format($grandTotal, $currencyCode) }}<br>
        Sudah dibayar: {{ $money->format($paidTotal, $currencyCode) }}<br>
        Sisa tagihan: {{ $money->format($balanceDue, $currencyCode) }}
    </div>
    @if($notes)
        <p>{{ $notes }}</p>
    @endif
@endsection
