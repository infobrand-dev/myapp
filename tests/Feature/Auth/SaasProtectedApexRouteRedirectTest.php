<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SaasProtectedApexRouteRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.mode', 'saas');
        config()->set('multitenancy.saas_domain', 'example.test');
        config()->set('multitenancy.platform_admin_subdomain', 'dash');

        Route::middleware(['web', 'auth'])->group(function () {
            Route::get('/_protected-apex-probe', fn () => 'ok');
            Route::get('/_protected-apex-probe.json', fn () => response()->json(['ok' => true]));
        });
    }

    public function test_guest_html_request_to_protected_apex_route_redirects_before_tenant_resolution(): void
    {
        $this->get('http://example.test/_protected-apex-probe')
            ->assertRedirect(route('login'));
    }

    public function test_guest_json_request_to_protected_apex_route_returns_unauthorized_before_tenant_resolution(): void
    {
        $this->getJson('http://example.test/_protected-apex-probe.json')
            ->assertUnauthorized();
    }
}
