<?php

namespace App\Http\Controllers;

use App\Models\StoredFile;
use App\Models\StorageProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class PlatformStorageController extends Controller
{
    public function index(): View
    {
        $profiles = StorageProfile::query()
            ->withCount('storedFiles')
            ->orderByDesc('is_default')
            ->orderBy('visibility_scope')
            ->orderBy('priority')
            ->orderBy('name')
            ->get();

        $atRiskFiles = Schema::hasTable('stored_files')
            ? StoredFile::query()
                ->with('storageProfile')
                ->whereNotNull('storage_profile_id')
                ->where(function ($query) {
                    $query->where('availability_status', '!=', 'available')
                        ->orWhereHas('storageProfile', fn ($builder) => $builder->where('is_active', false));
                })
                ->latest('id')
                ->limit(50)
                ->get()
            : collect();

        return view('platform.storage.index', [
            'profiles' => $profiles,
            'atRiskFiles' => $atRiskFiles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if (!empty($data['is_default'])) {
            StorageProfile::query()
                ->where('visibility_scope', $data['visibility_scope'])
                ->update(['is_default' => false]);
        }

        StorageProfile::query()->create($this->payload($data));

        return back()->with('status', 'Storage profile berhasil dibuat.');
    }

    public function update(Request $request, StorageProfile $profile): RedirectResponse
    {
        $data = $this->validated($request, $profile);

        if (!empty($data['is_default'])) {
            StorageProfile::query()
                ->where('visibility_scope', $data['visibility_scope'])
                ->whereKeyNot($profile->id)
                ->update(['is_default' => false]);
        }

        $profile->update($this->payload($data, $profile));

        return back()->with('status', "Storage profile {$profile->name} berhasil diperbarui.");
    }

    public function toggle(Request $request, StorageProfile $profile): RedirectResponse
    {
        $profile->forceFill([
            'is_active' => $request->boolean('is_active'),
        ])->save();

        return back()->with('status', "Storage profile {$profile->name} berhasil diperbarui.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?StorageProfile $profile = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:80', Rule::unique('storage_profiles', 'code')->ignore($profile?->id)],
            'name' => ['required', 'string', 'max:120'],
            'driver' => ['required', Rule::in(['local', 's3'])],
            'visibility_scope' => ['required', Rule::in(['public', 'private'])],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'failure_mode' => ['nullable', Rule::in(['mark_unreachable'])],
            'purposes' => ['nullable', 'string', 'max:255'],
            'bucket' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:60'],
            'endpoint' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500'],
            'root_path' => ['nullable', 'string', 'max:255'],
            'access_key_id' => ['nullable', 'string', 'max:255'],
            'secret_access_key' => ['nullable', 'string', 'max:5000'],
            'use_path_style_endpoint' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $data, ?StorageProfile $profile = null): array
    {
        $purposes = collect(explode(',', (string) ($data['purposes'] ?? '')))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->values()
            ->all();

        $payload = [
            'code' => trim((string) $data['code']),
            'name' => trim((string) $data['name']),
            'driver' => $data['driver'],
            'visibility_scope' => $data['visibility_scope'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'is_default' => (bool) ($data['is_default'] ?? false),
            'weight' => max(1, (int) ($data['weight'] ?? 100)),
            'priority' => max(1, (int) ($data['priority'] ?? 100)),
            'failure_mode' => $data['failure_mode'] ?? 'mark_unreachable',
            'purposes' => $purposes === [] ? null : $purposes,
            'bucket' => $this->nullableString($data['bucket'] ?? null),
            'region' => $this->nullableString($data['region'] ?? null),
            'endpoint' => $this->nullableString($data['endpoint'] ?? null),
            'url' => $this->nullableString($data['url'] ?? null),
            'root_path' => $this->nullableString($data['root_path'] ?? null),
            'use_path_style_endpoint' => (bool) ($data['use_path_style_endpoint'] ?? false),
        ];

        $accessKey = $this->nullableString($data['access_key_id'] ?? null);
        $secretKey = $this->nullableString($data['secret_access_key'] ?? null);

        if ($accessKey !== null || !$profile) {
            $payload['access_key_id'] = $accessKey;
        }

        if ($secretKey !== null || !$profile) {
            $payload['secret_access_key'] = $secretKey;
        }

        return $payload;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
