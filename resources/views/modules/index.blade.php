<x-app-layout>
    <x-slot name="header">
        <div class="container-xl">
            <h2 class="h3 mb-0">Modules</h2>
        </div>
    </x-slot>

    <div class="container-xl py-3">
        @if (session('status'))
            <div class="alert alert-info">{{ session('status') }}</div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                    <tr>
                        <th>Module</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Dependencies</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($modules as $module)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $module['name'] }}</div>
                                <div class="text-muted small">{{ $module['slug'] }}</div>
                                @if($module['description'])
                                    <div class="text-muted small">{{ $module['description'] }}</div>
                                @endif
                            </td>
                            <td>{{ $module['version'] ?: '-' }}</td>
                            <td>
                                @if(!$module['installed'])
                                    <span class="badge bg-secondary-lt text-secondary">Not Installed</span>
                                @elseif($module['active'])
                                    <span class="badge bg-success-lt text-success">Active</span>
                                @else
                                    <span class="badge bg-warning-lt text-warning">Installed</span>
                                @endif
                            </td>
                            <td>
                                @if(empty($module['requires']))
                                    <span class="text-muted">-</span>
                                @else
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($module['requires'] as $req)
                                            <span class="badge bg-azure-lt text-azure">{{ $req }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-list justify-content-end">
                                    @if(!$module['installed'])
                                        <form method="POST" action="{{ route('modules.install', $module['slug']) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">Install</button>
                                        </form>
                                    @elseif(!$module['active'])
                                        <form method="POST" action="{{ route('modules.activate', $module['slug']) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Activate</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('modules.deactivate', $module['slug']) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Deactivate</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Tidak ada module manifest yang ditemukan.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

