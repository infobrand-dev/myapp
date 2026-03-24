@extends('errors.layout')

@section('code', '500')
@section('title', 'Terjadi Kesalahan')

@section('description')
    Terjadi kesalahan di sisi server. Tim kami sudah otomatis mendapatkan notifikasi.
    Coba muat ulang halaman, atau kembali beberapa saat lagi.
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="btn btn-primary">Muat Ulang</a>
    @auth
        <a href="{{ route('dashboard') }}" class="btn btn-outline">Ke Dashboard</a>
    @endauth
@endsection
