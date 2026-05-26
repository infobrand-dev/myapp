@extends('emails.layout')

@section('subject', 'Sales Order ' . $documentNumber)

@section('content')
    @php($money = app(\App\Support\MoneyFormatter::class))
    <h1>Sales Order {{ $documentNumber }}</h1>
    <p>Halo {{ $customerName }}, berikut sales order terbaru dari kami.</p>
    <div class="info-box">
        Tanggal: {{ $documentDate ?: '-' }}<br>
        Estimasi kirim: {{ $expectedDeliveryDate ?: '-' }}<br>
        Total: {{ $money->format($grandTotal, $currencyCode) }}
    </div>
    @if($notes)
        <p>{{ $notes }}</p>
    @endif
@endsection
