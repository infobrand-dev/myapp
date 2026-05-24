<?php

namespace App\Services;

use App\Mail\UserInvitationMail;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
use App\Support\TenantContext;
use App\Support\UserAccessManager;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserInvitationService
{
    public function create(array $data, User $actor): UserInvitation
    {
        $tenantId = TenantContext::currentId();
        $tenant = Tenant::query()->whereKey($tenantId)->firstOrFail();
        $token = bin2hex(random_bytes(32));

        return DB::transaction(function () use ($data, $actor, $tenantId, $tenant, $token): UserInvitation {
            UserInvitation::query()
                ->where('tenant_id', $tenantId)
                ->where('email', $data['email'])
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);

            $invitation = UserInvitation::query()->create([
                'tenant_id' => $tenantId,
                'invited_by_user_id' => $actor->id,
                'name' => $data['name'] ?? null,
                'email' => $data['email'],
                'role_name' => $data['role'],
                'company_ids' => array_values($data['company_ids'] ?? []),
                'branch_ids' => array_values($data['branch_ids'] ?? []),
                'default_company_id' => $data['default_company_id'] ?? null,
                'default_branch_id' => $data['default_branch_id'] ?? null,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
                'meta' => [
                    'created_from' => 'tenant_user_invite',
                ],
            ]);

            Mail::to($invitation->email)->queue(
                new UserInvitationMail(
                    $invitation,
                    $tenant,
                    $this->acceptUrl($tenant, $invitation, $token)
                )
            );

            return $invitation;
        });
    }

    public function ensureAcceptable(UserInvitation $invitation, string $token): void
    {
        if ((int) $invitation->tenant_id !== (int) TenantContext::currentId()) {
            abort(404);
        }

        if (!$invitation->isPending()) {
            throw ValidationException::withMessages([
                'email' => 'Undangan ini sudah tidak valid atau sudah dipakai.',
            ]);
        }

        if (!hash_equals($invitation->token_hash, hash('sha256', $token))) {
            abort(403, 'Token undangan tidak valid.');
        }
    }

    public function accept(UserInvitation $invitation, string $token, array $data): User
    {
        $this->ensureAcceptable($invitation, $token);

        return DB::transaction(function () use ($invitation, $data, $token): User {
            $lockedInvitation = UserInvitation::query()
                ->whereKey($invitation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureAcceptable($lockedInvitation, $token);

            $tenant = Tenant::query()
                ->whereKey($lockedInvitation->tenant_id)
                ->active()
                ->firstOrFail();

            $existingUser = User::query()
                ->where('tenant_id', $tenant->id)
                ->where('email', $lockedInvitation->email)
                ->first();

            if ($existingUser) {
                throw ValidationException::withMessages([
                    'email' => 'Email ini sudah terdaftar pada workspace tersebut.',
                ]);
            }

            $role = Role::query()
                ->where('tenant_id', $tenant->id)
                ->where('guard_name', 'web')
                ->where('name', $lockedInvitation->role_name)
                ->firstOrFail();

            $user = User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $lockedInvitation->email,
                'password' => Hash::make($data['password']),
            ]);

            $user->syncRoles([$role->name]);

            app(UserAccessManager::class)->sync(
                $user,
                $lockedInvitation->company_ids ?? [],
                $lockedInvitation->branch_ids ?? [],
                $lockedInvitation->default_company_id,
                $lockedInvitation->default_branch_id
            );

            $lockedInvitation->forceFill([
                'accepted_at' => now(),
                'meta' => array_merge((array) ($lockedInvitation->meta ?? []), [
                    'accepted_user_id' => $user->id,
                ]),
            ])->save();

            event(new Registered($user));

            Auth::guard('web')->login($user);

            return $user;
        });
    }

    public function acceptUrl(Tenant $tenant, UserInvitation $invitation, string $token): string
    {
        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
        $root = $scheme . '://' . $tenant->slug . '.' . config('multitenancy.saas_domain');

        URL::forceRootUrl($root);

        try {
            return URL::temporarySignedRoute(
                'register.invitations.accept',
                now()->addDays(7),
                [
                    'invitation' => $invitation->id,
                    'token' => $token,
                ]
            );
        } finally {
            URL::forceRootUrl($appUrl);
        }
    }
}
