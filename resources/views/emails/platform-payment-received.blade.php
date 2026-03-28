@extends('emails.layout')

@section('subject', 'Pembayaran diterima untuk ' . $invoice->invoice_number)

@section('content')
    <h1>Pembayaran Berhasil Diterima</h1>

    <p>
        Pembayaran untuk invoice <strong>{{ $invoice->invoice_number }}</strong> telah kami terima.
    </p>

    <div class="info-box">
        <strong>Detail Pembayaran</strong>
        Invoice: {{ $invoice->invoice_number }}<br>
        Amount: {{ number_format((float) $payment->amount, 0, ',', '.') }} {{ $payment->currency }}<br>
        Channel: {{ $payment->payment_channel ?: '-' }}<br>
        Paid at: {{ optional($payment->paid_at)->format('d M Y H:i') ?: '-' }}
    </div>

    <div class="btn-wrap">
        <a href="{{ $invoiceUrl }}" class="btn">Lihat Invoice</a>
    </div>
@endsection
