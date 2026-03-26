@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $account->exists ? 'Edit Account' : 'Tambah Account' }}</h2>
        <div class="text-muted small">Gunakan mailbox khusus operasional, bukan mailbox campaign blast.</div>
    </div>
    <a href="{{ route('email-inbox.accounts.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<form method="POST" action="{{ $account->exists ? route('email-inbox.accounts.update', $account) : route('email-inbox.accounts.store') }}">
    @csrf
    @if($account->exists)
        @method('PUT')
    @endif

    <div class="row row-cards">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Identitas</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $account->name) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email_address" value="{{ old('email_address', $account->email_address) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Provider</label>
                        <input type="text" name="provider" value="{{ old('provider', $account->provider) }}" class="form-control" placeholder="gmail, outlook, zoho, custom">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mode</label>
                        <select name="direction_mode" class="form-select">
                            @foreach(['inbound_outbound' => 'Inbound + Outbound', 'inbound' => 'Inbound only', 'outbound' => 'Outbound only'] as $key => $label)
                                <option value="{{ $key }}" @selected(old('direction_mode', $account->direction_mode) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="form-check">
                        <input type="hidden" name="sync_enabled" value="0">
                        <input class="form-check-input" type="checkbox" name="sync_enabled" value="1" @checked(old('sync_enabled', $account->sync_enabled))>
                        <span class="form-check-label">Aktifkan scheduler sync</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Inbound IMAP</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label">Host</label>
                            <input type="text" name="inbound_host" value="{{ old('inbound_host', $account->inbound_host) }}" class="form-control">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Port</label>
                            <input type="number" name="inbound_port" value="{{ old('inbound_port', $account->inbound_port) }}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="inbound_username" value="{{ old('inbound_username', $account->inbound_username) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="inbound_password" class="form-control" placeholder="{{ $account->exists ? 'Kosongkan jika tidak berubah' : '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Encryption</label>
                        <select name="inbound_encryption" class="form-select">
                            @foreach(['ssl' => 'SSL', 'tls' => 'TLS', 'none' => 'None'] as $key => $label)
                                <option value="{{ $key }}" @selected(old('inbound_encryption', $account->inbound_encryption) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="form-check">
                        <input type="hidden" name="inbound_validate_cert" value="0">
                        <input class="form-check-input" type="checkbox" name="inbound_validate_cert" value="1" @checked(old('inbound_validate_cert', $account->inbound_validate_cert))>
                        <span class="form-check-label">Validasi sertifikat server</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Outbound SMTP</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Host</label>
                            <input type="text" name="outbound_host" value="{{ old('outbound_host', $account->outbound_host) }}" class="form-control">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Port</label>
                            <input type="number" name="outbound_port" value="{{ old('outbound_port', $account->outbound_port) }}" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Encryption</label>
                            <select name="outbound_encryption" class="form-select">
                                @foreach(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'] as $key => $label)
                                    <option value="{{ $key }}" @selected(old('outbound_encryption', $account->outbound_encryption) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">From Name</label>
                            <input type="text" name="outbound_from_name" value="{{ old('outbound_from_name', $account->outbound_from_name) }}" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="outbound_username" value="{{ old('outbound_username', $account->outbound_username) }}" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="outbound_password" class="form-control" placeholder="{{ $account->exists ? 'Kosongkan jika tidak berubah' : '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Reply-To</label>
                            <input type="email" name="outbound_reply_to" value="{{ old('outbound_reply_to', $account->outbound_reply_to) }}" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
