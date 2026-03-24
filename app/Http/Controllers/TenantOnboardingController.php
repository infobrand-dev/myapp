<?php

namespace App\Http\Controllers;

use App\Mail\TenantWelcomeMail;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantRoleProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantOnboardingController extends Controller
{
    /**
     * Show the new-tenant registration form.
     * Only accessible in SaaS mode.
     */
    public function create()
    {
        abort_unless(config('multitenancy.mode') === 'saas', 404);

        return view('onboarding.create');
    }

    /**
     * Validate, create the tenant + super-admin, then redirect to the tenant's login page.
     */
    public function store(Request $request)
    {
        abort_unless(config('multitenancy.mode') === 'saas', 404);

        $saasDomain     = config('multitenancy.saas_domain');
        $reservedSlugs  = config('multitenancy.reserved_slugs', []);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/',
                Rule::notIn($reservedSlugs),
                Rule::unique('tenants', 'slug'),
            ],
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'slug.regex'   => 'Subdomain hanya boleh huruf kecil, angka, dan tanda hubung, dan tidak boleh diawali/diakhiri tanda hubung.',
            'slug.not_in'  => 'Subdomain tersebut tidak tersedia. Pilih nama lain.',
            'slug.unique'  => 'Subdomain tersebut sudah dipakai. Pilih nama lain.',
        ]);

        $tenant = DB::transaction(function () use ($data) {
            // 1. Create the tenant record
            $tenant = Tenant::create([
                'name'      => $data['company_name'],
                'slug'      => $data['slug'],
                'is_active' => true,
            ]);

            // 2. Provision default roles and permissions for this tenant
            app(TenantRoleProvisioner::class)->ensureForTenant($tenant->id);

            // 3. Create the super-admin user scoped to this tenant
            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId($tenant->id);

            try {
                $user = User::create([
                    'tenant_id' => $tenant->id,
                    'name'      => $data['name'],
                    'email'     => $data['email'],
                    'password'  => Hash::make($data['password']),
                ]);

                $superAdminRole = Role::query()
                    ->where('name', 'Super-admin')
                    ->where('tenant_id', $tenant->id)
                    ->where('guard_name', 'web')
                    ->firstOrFail();

                $user->assignRole($superAdminRole);
            } finally {
                $registrar->setPermissionsTeamId(null);
                $registrar->forgetCachedPermissions();
            }

            return $tenant;
        });

        $loginUrl = 'http' . (request()->isSecure() ? 's' : '') . '://'
            . $tenant->slug . '.' . config('multitenancy.saas_domain')
            . '/login?registered=1';

        // Send welcome email (queued — won't block the redirect).
        // Wrapped in try-catch so a mail/queue misconfiguration never breaks onboarding.
        try {
            Mail::to($data['email'])->queue(
                new TenantWelcomeMail(
                    adminName:  $data['name'],
                    adminEmail: $data['email'],
                    tenantName: $tenant->name,
                    tenantSlug: $tenant->slug,
                    loginUrl:   $loginUrl,
                )
            );
        } catch (\Throwable $e) {
            logger()->error('TenantOnboarding: failed to queue welcome email', [
                'tenant' => $tenant->slug,
                'email'  => $data['email'],
                'error'  => $e->getMessage(),
            ]);
        }

        return redirect()->away($loginUrl);
    }
}
