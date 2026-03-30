@extends('emails.layout')

@section('subject', 'Invoice ' . $invoice->invoice_number)

@section('content')
    @php
        $money = app(\App\Support\MoneyFormatter::class);
    @endphp
    <h1>Invoice Baru untuk {{ optional($invoice->tenant)->name }}</h1>

    <p>
        Kami telah menerbitkan invoice baru untuk langganan plan Anda di <strong>{{ config('app.name') }}</strong>.
    </p>

    <div class="info-box">
        <strong>Detail Invoice</strong>
        Invoice: {{ $invoice->invoice_number }}<br>
        Plan: {{ optional($invoice->plan)->name ?? '-' }}<br>
        Amount: {{ $money->format((float) $invoice->amount, $invoice->currency) }}<br>
        Due: {{ optional($invoice->due_at)->format('d M Y H:i') ?: '-' }}
    </div>

    <div class="btn-wrap">
        <a href="{{ $invoiceUrl }}" class="btn">Lihat Invoice</a>
    </div>
@endsection
