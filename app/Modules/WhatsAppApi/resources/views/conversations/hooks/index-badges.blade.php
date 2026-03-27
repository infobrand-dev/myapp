<span class="badge bg-azure-lt text-azure">{{ $instanceName }}</span>
<span class="badge {{ $instanceStatus === 'connected' ? 'text-bg-success' : ($instanceStatus === 'error' ? 'text-bg-danger' : 'text-bg-secondary') }}">{{ $instanceStatus }}</span>
