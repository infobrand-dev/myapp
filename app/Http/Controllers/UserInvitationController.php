<?php

namespace App\Http\Controllers;

use App\Models\UserInvitation;
use App\Services\UserInvitationService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserInvitationController extends Controller
{
    public function store(Request $request, UserInvitationService $invitations): RedirectResponse
    {
        $tenantId = TenantContext::currentId();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('guard_name', 'web'))],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer', Rule::exists('companies', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'default_company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', Rule::exists('branches', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'default_branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
        ]);

        $invitations->create($data, $request->user());

        return redirect()->route('users.index')->with('status', 'Undangan user berhasil dikirim.');
    }

    public function show(Request $request, UserInvitation $invitation, UserInvitationService $invitations): View
    {
        $token = (string) $request->query('token', '');
        $invitations->ensureAcceptable($invitation, $token);

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    public function accept(Request $request, UserInvitation $invitation, UserInvitationService $invitations): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'token' => ['required', 'string'],
        ]);

        $user = $invitations->accept($invitation, $data['token'], $data);

        $request->session()->regenerate();
        $request->setUserResolver(fn () => $user);
        $request->session()->put('tenant_id', $user->tenant_id);
        $request->session()->put('tenant_slug', optional($user->tenant)->slug);

        return redirect()->route('verification.notice')
            ->with('status', 'Akun berhasil diaktifkan. Verifikasi email dulu sebelum masuk ke dashboard.');
    }
}
