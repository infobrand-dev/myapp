<x-app-layout>
    <x-slot name="header">
        <div class="container-xl">
            <h2 class="h3 mb-0">Sample Data</h2>
        </div>
    </x-slot>

    <div class="container-xl py-3">
        @if (session('status'))
            <div class="alert alert-info">{{ session('status') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <h3 class="card-title mb-2">Active Modules</h3>
                <p class="text-muted mb-0">
                    Halaman ini menampilkan module yang sedang aktif. Module yang punya konfigurasi <code>sample_data</code> di
                    <code>module.json</code> akan bisa langsung generate data contoh dari sini.
                </p>
            </div>
        </div>

        @if(($bundles ?? collect())->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Bundles</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($bundles as $bundle)
                            <div class="col-lg-6">
                                <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center justify-content-between gap-3">
                                            <div>
                                                <div class="fw-semibold">{{ $bundle['name'] }}</div>
                                                <div class="text-muted small">{{ $bundle['slug'] }}</div>
                                            </div>
                                            <span class="badge bg-success-lt text-success">One Click</span>
                                        </div>
                                        <p class="text-muted small mt-2 mb-0">{{ $bundle['description'] }}</p>
                                        @if($bundle['supports_transactional_mail'])
                                            <div class="text-muted small mt-2">
                                                Invoice/receipt tenant ikut dicoba. Jika queue bukan <code>sync</code>, jalankan worker agar email queued terkirim.
                                            </div>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('sample-data.bundles.run', $bundle['slug']) }}" class="mt-3">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">Generate Bundle</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                    <tr>
                        <th>Module</th>
                        <th>Sample Data</th>
                        <th>Seeder</th>
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
                            <td>
                                @if($module['ready'])
                                    <span class="badge bg-success-lt text-success">Ready</span>
                                    @if($module['sample_description'])
                                        <div class="text-muted small mt-1">{{ $module['sample_description'] }}</div>
                                    @endif
                                @else
                                    <span class="badge bg-secondary-lt text-secondary">Not Available</span>
                                    <div class="text-muted small mt-1">Tambahkan konfigurasi <code>sample_data.seeders</code> di module manifest.</div>
                                @endif
                            </td>
                            <td>
                                @if($module['ready'])
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($module['seeders'] as $seeder)
                                            <span class="badge bg-azure-lt text-azure">{{ class_basename($seeder) }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($module['ready'])
                                    <form method="POST" action="{{ route('sample-data.store', $module['slug']) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">Generate</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Belum ada handler</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada module aktif selain Sample Data.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
