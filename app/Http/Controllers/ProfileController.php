<?php

namespace App\Http\Controllers;

use App\Services\TenantStorageUsageService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            $releasedBytes = $user->avatar ? app(TenantStorageUsageService::class)->fileSize('public', $user->avatar) : 0;
            app(TenantStorageUsageService::class)->ensureCanStoreUpload(
                $request->file('avatar'),
                $tenantId,
                null,
                $releasedBytes
            );

            // Delete the old avatar file if it exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
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
