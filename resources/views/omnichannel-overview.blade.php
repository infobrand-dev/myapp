@extends('layouts.admin')

@section('title', 'Omnichannel Overview')

@section('content')
@php
    $storageFormatter = app(\App\Support\StorageSizeFormatter::class);
    $capacityCards = [
        ['label' => 'Users', 'state' => $capacity['users'] ?? null],
        ['label' => 'Social Accounts', 'state' => $capacity['social_accounts'] ?? null],
        ['label' => 'WhatsApp Instances', 'state' => $capacity['whatsapp_instances'] ?? null],
        ['label' => 'Live Chat Widgets', 'state' => $capacity['live_chat_widgets'] ?? null],
    ];

    $statusBadge = function (?string $status): string {
        return match ($status) {
            'at_limit', 'over_limit' => 'bg-red-lt text-red',
            'near_limit' => 'bg-orange-lt text-orange',
            default => 'bg-green-lt text-green',
        };
    };
@endphp

<div class="page-header mb-3">
    <div class="row align-items-center w-100">
        <div class="col">
            <div class="page-pretitle">Omnichannel</div>
            <h2 class="page-title">Omnichannel Overview</h2>
            <div class="text-muted">Ringkasan kesehatan channel, beban percakapan, kualitas respons, automation, dan kapasitas workspace.</div>
        </div>
        <div class="col-auto">
            <div class="d-flex gap-2">
                <a href="{{ route('omnichannel.overview.export', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="ti ti-download me-1"></i>Export CSV
                </a>
                <a href="{{ route('conversations.index') }}" class="btn btn-primary">
                    <i class="ti ti-messages me-1"></i>Buka Inbox
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('omnichannel.overview') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-12 col-md-4 col-xl-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-filter me-1"></i>Terapkan
                </button>
                <a href="{{ route('omnichannel.overview') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Connected Channels</div>
                <div class="display-6 fw-bold">{{ number_format($channelHealth['total_connected'] ?? 0) }}</div>
                <div class="text-muted small mt-2">WhatsApp, social account, dan live chat yang aktif.</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Conversations</div>
                <div class="display-6 fw-bold">{{ number_format($volume['total_period'] ?? 0) }}</div>
                <div class="text-muted small mt-2">Percakapan yang bergerak pada rentang tanggal terpilih.</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Waiting Reply</div>
                <div class="display-6 fw-bold">{{ number_format($volume['waiting_reply'] ?? 0) }}</div>
                <div class="text-muted small mt-2">Percakapan open yang terakhir ditunggu balasan tim.</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary text-uppercase small fw-bold">Estimated Reply</div>
                <div class="display-6 fw-bold">
                    {{ $responseQuality['estimated_reply_minutes'] !== null ? number_format($responseQuality['estimated_reply_minutes']) . 'm' : 'N/A' }}
                </div>
                <div class="text-muted small mt-2">Estimasi saat ini berdasarkan antrean terbuka dan median respons.</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Channel Health</h3>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">WhatsApp</div>
                            <div class="text-muted small">Instance siap dipakai dan yang butuh perhatian.</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">{{ number_format($channelHealth['whatsapp_connected'] ?? 0) }} aktif</div>
                            <div class="small text-danger">{{ number_format($channelHealth['whatsapp_issue'] ?? 0) }} issue</div>
                        </div>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">Instagram / Facebook / X</div>
                            <div class="text-muted small">Akun sosial yang sudah connect dan yang bermasalah.</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">{{ number_format($channelHealth['social_connected'] ?? 0) }} aktif</div>
                            <div class="small text-danger">{{ number_format($channelHealth['social_issue'] ?? 0) }} issue</div>
                        </div>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">Live Chat</div>
                            <div class="text-muted small">Widget live chat yang sedang aktif.</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">{{ number_format($channelHealth['live_chat_active'] ?? 0) }} aktif</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Conversation Flow</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-secondary text-uppercase small fw-bold">Inbound</div>
                            <div class="h2 mb-0">{{ number_format($volume['inbound_period'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-secondary text-uppercase small fw-bold">Open</div>
                            <div class="h2 mb-0">{{ number_format($volume['open'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-secondary text-uppercase small fw-bold">Unassigned</div>
                            <div class="h2 mb-0">{{ number_format($volume['unassigned'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-secondary text-uppercase small fw-bold">Auto Reply Channels</div>
                            <div class="h2 mb-0">{{ number_format($automation['auto_reply_channels'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="fw-semibold mb-2">Response Quality</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-secondary text-uppercase small fw-bold">Median First Response</div>
                                <div class="h3 mb-0">
                                    {{ $responseQuality['first_response_median_minutes'] !== null ? number_format($responseQuality['first_response_median_minutes']) . 'm' : 'N/A' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-secondary text-uppercase small fw-bold">Avg First Response</div>
                                <div class="h3 mb-0">
                                    {{ $responseQuality['first_response_average_minutes'] !== null ? number_format($responseQuality['first_response_average_minutes']) . 'm' : 'N/A' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-secondary text-uppercase small fw-bold">SLA ≤ {{ $responseQuality['sla_target_minutes'] }}m</div>
                                <div class="h3 mb-0">
                                    {{ $responseQuality['sla_hit_rate'] !== null ? number_format($responseQuality['sla_hit_rate']) . '%' : 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-muted small">
                        @if($responseQuality['has_data'])
                            Metrik respons dihitung dari percakapan yang sudah memiliki jejak pesan masuk dan balasan keluar.
                        @else
                            Belum cukup data respons untuk menghitung median dan SLA secara stabil.
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Channel Breakdown</h3>
            </div>
            <div class="card-body">
                @if(!empty($channelBreakdown))
                    <div class="list-group list-group-flush">
                        @foreach($channelBreakdown as $item)
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">{{ $item['label'] }}</div>
                                    <div class="text-muted small">{{ $item['channel'] }}</div>
                                </div>
                                <strong>{{ number_format($item['total']) }}</strong>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted small">Belum ada data channel pada rentang tanggal ini.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Team Workload</h3>
            </div>
            <div class="card-body">
                @if(!empty($teamWorkload))
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th class="text-end">Assigned Open</th>
                                    <th class="text-end">Active in Period</th>
                                    <th class="text-end">Overdue Queue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teamWorkload as $agent)
                                    <tr>
                                        <td>{{ $agent['name'] }}</td>
                                        <td class="text-end">{{ number_format($agent['assigned_open_count']) }}</td>
                                        <td class="text-end">{{ number_format($agent['active_period_count']) }}</td>
                                        <td class="text-end">{{ number_format($agent['overdue_queue_count']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted small">Belum ada workload tim yang bisa ditampilkan.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Top Channel Trend Harian</h3>
            </div>
            <div class="card-body">
                @if(!empty($channelTrend))
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Channel</th>
                                    <th class="text-end">Conversations</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($channelTrend as $trend)
                                    <tr>
                                        <td>{{ $trend['day'] }}</td>
                                        <td>{{ $trend['label'] }}</td>
                                        <td class="text-end">{{ number_format($trend['total']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted small">Belum ada trend harian pada rentang tanggal ini.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Waiting Queue</h3>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span>&lt; 5 menit</span>
                        <strong>{{ number_format($responseQuality['waiting_buckets']['lt_5'] ?? 0) }}</strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span>5 - 15 menit</span>
                        <strong>{{ number_format($responseQuality['waiting_buckets']['m5_15'] ?? 0) }}</strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span>15 - 60 menit</span>
                        <strong>{{ number_format($responseQuality['waiting_buckets']['m15_60'] ?? 0) }}</strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span>&gt; 1 jam</span>
                        <strong>{{ number_format($responseQuality['waiting_buckets']['gt_60'] ?? 0) }}</strong>
                    </div>
                </div>
                <div class="text-muted small mt-3">Gunakan blok ini untuk melihat antrean yang mulai menua dan butuh intervensi tim.</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Plan & Capacity</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($capacityCards as $card)
                        @php $state = $card['state']; @endphp
                        <div class="col-md-6 col-xl-3">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="text-secondary text-uppercase small fw-bold">{{ $card['label'] }}</div>
                                    @if($state)
                                        <span class="badge {{ $statusBadge($state['status'] ?? 'ok') }}">{{ $state['status'] ?? 'ok' }}</span>
                                    @endif
                                </div>
                                <div class="mt-2 fw-semibold">
                                    @if($state && $state['limit'] !== null)
                                        {{ number_format((int) ($state['usage'] ?? 0)) }} / {{ number_format((int) $state['limit']) }}
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 border rounded-3 p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="text-secondary text-uppercase small fw-bold">Total Storage</div>
                            <div class="fw-semibold mt-2">
                                {{ $storageFormatter->formatBytes((int) ($capacity['storage']['usage'] ?? $capacity['storage']['used_bytes'] ?? 0)) }}
                                @if(($capacity['storage']['limit'] ?? null) !== null)
                                    / {{ $storageFormatter->formatBytes((int) $capacity['storage']['limit']) }}
                                @endif
                            </div>
                        </div>
                        <span class="badge {{ $statusBadge($capacity['storage']['status'] ?? 'ok') }}">{{ $capacity['storage']['status'] ?? 'ok' }}</span>
                    </div>
                    <div class="text-muted small mt-2">Storage total workspace dihitung lintas aset file yang memang dimiliki tenant dan sudah dikenali sistem.</div>
                </div>

                <div class="mt-3 d-flex flex-wrap gap-2">
                    <a href="{{ route('settings.subscription') }}" class="btn btn-outline-secondary btn-sm">Lihat Subscription</a>
                    <a href="{{ route('settings.addons') }}" class="btn btn-outline-secondary btn-sm">Lihat Add-ons</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
