<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TenantDomain;
use App\Services\TenantCustomDomainService;
use App\Support\PlanFeature;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TenantCustomDomainController extends Controller
{
    public function store(Request $request, TenantCustomDomainService $service): RedirectResponse
    {
        $tenant = TenantContext::currentTenant();
        abort_unless($tenant, 404);
        $this->ensureFeature();

        $data = $request->validate([
            'hostname' => ['required', 'string', 'max:255'],
        ]);

        $service->requestDomain($tenant, $data['hostname'], $request->user()?->id);

        return redirect()->route('settings.custom-domains')->with('status', 'Custom domain berhasil diajukan.');
    }

    public function sync(Request $request, TenantDomain $tenantDomain, TenantCustomDomainService $service): RedirectResponse
    {
        $this->assertTenantDomainOwnership($tenantDomain);
        $this->ensureFeature();

        $service->syncDomain($tenantDomain, $request->user()?->id);

        return redirect()->route('settings.custom-domains')->with('status', 'Status domain berhasil disinkronkan.');
    }

    public function promote(Request $request, TenantDomain $tenantDomain, TenantCustomDomainService $service): RedirectResponse
    {
        $this->assertTenantDomainOwnership($tenantDomain);
        $this->ensureFeature();

        $service->promoteCanonical($tenantDomain, $request->user()?->id);

        return redirect()->route('settings.custom-domains')->with('status', 'Domain canonical berhasil diperbarui.');
    }

    public function destroy(Request $request, TenantDomain $tenantDomain, TenantCustomDomainService $service): RedirectResponse
    {
        $this->assertTenantDomainOwnership($tenantDomain);
        $this->ensureFeature();

        try {
            $service->removeDomain($tenantDomain, $request->user()?->id);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return redirect()->route('settings.custom-domains')->withErrors([
                'domain' => 'Gagal menghapus domain: ' . $exception->getMessage(),
            ]);
        }

        return redirect()->route('settings.custom-domains')->with('status', 'Domain berhasil dihapus.');
    }

    private function assertTenantDomainOwnership(TenantDomain $tenantDomain): void
    {
        abort_unless((int) $tenantDomain->tenant_id === (int) TenantContext::currentId(), 404);
    }

    private function ensureFeature(): void
    {
        app(TenantPlanManager::class)->ensureFeature(
            PlanFeature::CUSTOM_DOMAINS,
            'Custom domain hanya tersedia untuk plan tenant yang lebih tinggi. Upgrade plan untuk mengaktifkan fitur ini.'
        );
    }
}
