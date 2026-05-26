@extends('emails.layout')

@section('subject', 'Quotation ' . $documentNumber)

@section('content')
    @php($money = app(\App\Support\MoneyFormatter::class))
    <h1>Quotation {{ $documentNumber }}</h1>
    <p>Halo {{ $customerName }}, berikut quotation terbaru dari kami.</p>
    <div class="info-box">
        Tanggal: {{ $documentDate ?: '-' }}<br>
        Berlaku sampai: {{ $validUntilDate ?: '-' }}<br>
        Total: {{ $money->format($grandTotal, $currencyCode) }}
    </div>
    @if($notes)
        <p>{{ $notes }}</p>
    @endif
@endsection
