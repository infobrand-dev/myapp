@extends('layouts.admin')

@section('title', $contact->name)

@section('content')
@php
    $hooks = app(\App\Support\HookManager::class);
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">CRM · Contacts</div>
            <h2 class="page-title">{{ $contact->name }}</h2>
            <p class="text-muted mb-0">
                {{ $contact->type === 'company' ? 'Company' : 'Individual' }}
                @if($contact->parentContact)
                    · Bekerja di {{ $contact->parentContact->name }}
                @endif
                · Scope {{ ucfirst(\App\Modules\Contacts\Support\ContactScope::detectLevel($contact)) }}
            </p>
        </div>
        <div class="col-auto d-flex gap-2">
            @foreach($hooks->render('contacts.show.header_actions', ['contact' => $contact]) as $hookedAction)
                {!! $hookedAction !!}
            @endforeach
            <a href="{{ route('contacts.edit', $contact) }}" class="btn btn-outline-primary">
                <i class="ti ti-pencil me-1"></i>Edit
            </a>
            <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informasi Utama</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal">Email</dt>
                    <dd class="col-sm-8">{{ $contact->email ?? '-' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">Telepon</dt>
                    <dd class="col-sm-8">{{ $contact->phone ?? '-' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">Mobile</dt>
                    <dd class="col-sm-8">
                        @if($contact->mobile)
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $contact->mobile) }}" target="_blank" class="text-decoration-none">
                                <i class="ti ti-brand-whatsapp text-green me-1"></i>{{ $contact->mobile }}
                            </a>
                        @else
                            -
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Website</dt>
                    <dd class="col-sm-8">
                        @if($contact->website)
                            <a href="{{ $contact->website }}" target="_blank" class="text-decoration-none">{{ $contact->website }}</a>
                        @else
                            -
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Jabatan</dt>
                    <dd class="col-sm-8">{{ $contact->job_title ?? '-' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">Industry</dt>
                    <dd class="col-sm-8">{{ $contact->industry ?? '-' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">Payment Term</dt>
                    <dd class="col-sm-8">{{ $contact->payment_term_days !== null ? $contact->payment_term_days . ' hari' : '-' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">Credit Limit</dt>
                    <dd class="col-sm-8">{{ $contact->credit_limit !== null ? $money->format((float) $contact->credit_limit, $currency) : '-' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">VAT/NPWP</dt>
                    <dd class="col-sm-8">{{ $contact->vat ?? '-' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">Company Registry</dt>
                    <dd class="col-sm-8">{{ $contact->company_registry ?? '-' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">Scope</dt>
                    <dd class="col-sm-8">
                        {{ $contact->company?->name ?? 'Tenant-wide' }}
                        @if($contact->branch)
                            · {{ $contact->branch->name }}
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Company Contact</dt>
                    <dd class="col-sm-8">
                        @if($contact->parentContact)
                            <a href="{{ route('contacts.show', $contact->parentContact) }}">{{ $contact->parentContact->name }}</a>
                        @else
                            -
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Contact Person</dt>
                    <dd class="col-sm-8">
                        @if($contact->contact_person_name || $contact->contact_person_phone)
                            <div>{{ $contact->contact_person_name ?? '-' }}</div>
                            <div class="text-muted small">{{ $contact->contact_person_phone ?? '-' }}</div>
                        @else
                            -
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Tags</dt>
                    <dd class="col-sm-8">
                        @if(!empty($contact->tags))
                            @foreach($contact->tags as $tag)
                                <span class="badge bg-azure-lt text-azure me-1">{{ $tag }}</span>
                            @endforeach
                        @else
                            -
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge {{ $contact->is_active ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">
                            {{ $contact->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Alamat</h3>
            </div>
            <div class="card-body">
                @if($contact->street || $contact->city || $contact->country)
                    <address class="mb-0">
                        @if($contact->street)<div>{{ $contact->street }}</div>@endif
                        @if($contact->street2)<div>{{ $contact->street2 }}</div>@endif
                        @if($contact->city || $contact->zip)
                            <div>{{ implode(' ', array_filter([$contact->city, $contact->zip])) }}</div>
                        @endif
                        @if($contact->state)<div>{{ $contact->state }}</div>@endif
                        @if($contact->country)<div>{{ $contact->country }}</div>@endif
                    </address>
                @else
                    <span class="text-muted">Belum ada alamat.</span>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Billing vs Shipping</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Billing Address</div>
                    <div>{{ $contact->billing_address ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-muted small">Shipping Address</div>
                    <div>{{ $contact->shipping_address ?: '-' }}</div>
                </div>
            </div>
        </div>

        @if($contact->notes)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Catatan</h3>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $contact->notes }}</p>
                </div>
            </div>
        @endif
    </div>

    @if($contact->type === 'company')
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Individu di Company ini</h3>
                    <div class="card-options">
                        <span class="badge bg-secondary-lt text-secondary">{{ $contact->employees->count() }} kontak</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter table-hover">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Jabatan</th>
                                    <th>Email</th>
                                    <th>Telepon</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($contact->employees as $employee)
                                    <tr>
                                        <td>
                                            <a href="{{ route('contacts.show', $employee) }}" class="fw-semibold text-decoration-none">
                                                {{ $employee->name }}
                                            </a>
                                        </td>
                                        <td class="text-muted">{{ $employee->job_title ?? '-' }}</td>
                                        <td class="text-muted">{{ $employee->email ?? '-' }}</td>
                                        <td class="text-muted">{{ $employee->phone ?? $employee->mobile ?? '-' }}</td>
                                        <td class="text-end align-middle">
                                            <a href="{{ route('contacts.show', $employee) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="Lihat Detail">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada individu terdaftar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@foreach($hooks->render('contacts.show.after_content', ['contact' => $contact]) as $hookedContent)
    {!! $hookedContent !!}
@endforeach
@endsection
