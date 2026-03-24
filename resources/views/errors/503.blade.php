@extends('errors.layout')

@section('code', '503')
@section('title', 'Sedang dalam Pemeliharaan')

@section('description')
    {{ config('app.name') }} sedang dalam pemeliharaan terjadwal dan akan segera kembali.
    Terima kasih atas kesabaran Anda.
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="btn btn-primary">Coba Lagi</a>
@endsection
