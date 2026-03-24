@extends('errors.layout')

@section('code', '403')
@section('title', 'Akses Ditolak')

@section('description')
    {{ $exception->getMessage() ?: 'Anda tidak memiliki izin untuk mengakses halaman atau melakukan tindakan ini.' }}
@endsection

@section('actions')
    @auth
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Ke Dashboard</a>
    @else
        <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
    @endauth
    <a href="javascript:history.back()" class="btn btn-outline">Kembali</a>
@endsection
