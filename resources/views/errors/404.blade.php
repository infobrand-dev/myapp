@extends('errors.layout')

@section('code', '404')
@section('title', 'Halaman Tidak Ditemukan')

@section('description')
    Halaman yang Anda cari tidak ada, sudah dipindahkan, atau URL-nya salah.
    Periksa kembali alamat yang Anda ketik.
@endsection

@section('actions')
    @auth
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Ke Dashboard</a>
    @else
        <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
    @endauth
    <a href="javascript:history.back()" class="btn btn-outline">Kembali</a>
@endsection
