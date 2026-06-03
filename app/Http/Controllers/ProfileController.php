<?php

namespace App\Http\Controllers;

use App\Services\StoredFileService;
use App\Services\TenantStorageUsageService;
use App\Services\WorkspaceMediaStorageService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $tenantId = TenantContext::currentId();
        $publicDisk = (string) config('workspace-files.public_disk', 'public');

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required',
                'email',
                'max:255',
                // Scoped to current tenant, excluding own ID — same pattern as UserController
                Rule::unique('users', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($user->id),
            ],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'avatar'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $releasedBytes = $user->avatar ? app(TenantStorageUsageService::class)->fileSize($publicDisk, $user->avatar) : 0;
            app(TenantStorageUsageService::class)->ensureCanStoreUpload(
                $request->file('avatar'),
                $tenantId,
                null,
                $releasedBytes
            );

            if ($user->avatar) {
                app(StoredFileService::class)->deletePublicAssetByPath($user->avatar, $publicDisk);
            }

            $stored = app(WorkspaceMediaStorageService::class)->storeUploadedFile($request->file('avatar'), 'avatars', $publicDisk);
            $data['avatar'] = $stored['path'];
        } else {
            // Don't overwrite avatar if no file was submitted
            unset($data['avatar']);
        }

        // Hash password only when provided; don't include if empty
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return back()->with('status', 'Profile updated.');
    }
}
