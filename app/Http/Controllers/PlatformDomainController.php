<?php

namespace App\Http\Controllers;

use App\Models\CloudflareSaasSetting;
use App\Models\TenantDomain;
use App\Services\TenantCustomDomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformDomainController extends Controller
{
    public function index(): View
    {
        return view('platform.domains.index', [
            'settings' => CloudflareSaasSetting::current(),
            'domains' => TenantDomain::query()
                ->with('tenant')
                ->latest('id')
                ->limit(100)
                ->get(),
            'statusCounts' => TenantDomain::query()
                ->selectRaw('status, count(*) as aggregate_count')
                ->groupBy('status')
                ->pluck('aggregate_count', 'status'),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['nullable', 'string', 'max:80'],
            'zone_id' => ['nullable', 'string', 'max:80'],
            'api_token' => ['nullable', 'string', 'max:5000'],
            'fallback_origin_hostname' => ['nullable', 'string', 'max:255'],
            'cname_target' => ['nullable', 'string', 'max:255'],
            'apex_proxying_enabled' => ['nullable', 'boolean'],
            'apex_ipv4_targets' => ['nullable', 'string'],
            'apex_ipv6_targets' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $settings = CloudflareSaasSetting::current();
        $token = trim((string) ($data['api_token'] ?? ''));

        $settings->fill([
            'account_id' => $this->nullableString($data['account_id'] ?? null),
            'zone_id' => $this->nullableString($data['zone_id'] ?? null),
            'fallback_origin_hostname' => $this->nullableString($data['fallback_origin_hostname'] ?? null),
            'cname_target' => $this->nullableString($data['cname_target'] ?? null),
            'apex_proxying_enabled' => $request->boolean('apex_proxying_enabled'),
            'apex_ipv4_targets' => $this->explodeLines($data['apex_ipv4_targets'] ?? ''),
            'apex_ipv6_targets' => $this->explodeLines($data['apex_ipv6_targets'] ?? ''),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($token !== '') {
            $settings->api_token = $token;
        }

        $settings->save();

        return redirect()->route('platform.domains.index')->with('status', 'Cloudflare SaaS settings berhasil disimpan.');
    }

    public function sync(Request $request, TenantDomain $tenantDomain, TenantCustomDomainService $service): RedirectResponse
    {
        $service->syncDomain($tenantDomain, $request->user()?->id);

        return redirect()->route('platform.domains.index')->with('status', 'Domain berhasil disinkronkan.');
    }

    public function audit(): View
    {
        return view('platform.domains.audit', [
            'settings' => CloudflareSaasSetting::current(),
            'problematicDomains' => TenantDomain::query()
                ->with('tenant')
                ->whereIn('status', [
                    TenantDomain::STATUS_BLOCKED,
                    TenantDomain::STATUS_FAILED,
                ])
                ->orWhere('last_error_code', 'provider_unreachable')
                ->latest('id')
                ->get(),
        ]);
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<int, string>
     */
    private function explodeLines(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $line) => trim($line),
            preg_split('/\r\n|\r|\n/', $value) ?: []
        )));
    }
}
