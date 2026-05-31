<?php

namespace Tests;

use App\Support\SaasHost;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.mode', 'standalone');
        URL::defaults([]);
    }

    public function actingAs(Authenticatable $user, $guard = null)
    {
        $this->syncTenantRouteDefaultsForUser($user);

        return parent::actingAs($user, $guard);
    }

    protected function prepareUrlForRequest($uri)
    {
        if ($this->isAbsoluteUrl($uri) || config('multitenancy.mode') !== 'saas') {
            return parent::prepareUrlForRequest($uri);
        }

        $host = $this->resolveHostForRelativeRequest((string) $uri);

        if ($host === null) {
            return parent::prepareUrlForRequest($uri);
        }

        $scheme = (string) (parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https');
        $path = ltrim((string) $uri, '/');

        return $scheme . '://' . $host . '/' . $path;
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null): TestResponse
    {
        $kernel = $this->app->make(HttpKernel::class);

        $files = array_merge($files, $this->extractFilesFromDataArray($parameters));
        $url = $this->prepareUrlForRequest($uri);
        $parts = parse_url($url);

        if (!empty($parts['host'])) {
            $server['HTTP_HOST'] = $parts['host'];
            $server['SERVER_NAME'] = $parts['host'];
        }

        if (!empty($parts['scheme'])) {
            $server['HTTPS'] = $parts['scheme'] === 'https' ? 'on' : 'off';
        }

        if (!empty($parts['port'])) {
            $server['SERVER_PORT'] = $parts['port'];
        }

        $symfonyRequest = SymfonyRequest::create(
            $url,
            $method,
            $parameters,
            $cookies,
            $files,
            array_replace($this->serverVariables, $server),
            $content
        );

        $response = $kernel->handle(
            $request = Request::createFromBase($symfonyRequest)
        );

        $kernel->terminate($request, $response);

        if ($this->followRedirects) {
            $response = $this->followRedirects($response);
        }

        return $this->createTestResponse($response, $request);
    }

    protected function syncTenantRouteDefaultsForUser(Authenticatable $user): void
    {
        URL::defaults([]);

        if (config('multitenancy.mode') !== 'saas') {
            return;
        }

        $tenantId = (int) data_get($user, 'tenant_id', 0);

        if ($tenantId <= 0) {
            return;
        }

        $user->loadMissing('tenant');

        if ($tenantId === 1 && $this->isPlatformSuperAdmin($user)) {
            URL::defaults(['account' => (string) config('multitenancy.platform_admin_subdomain', 'dash')]);

            return;
        }

        $slug = (string) optional($user->tenant)->slug;
        if ($slug !== '') {
            URL::defaults(['account' => $slug]);
        }
    }

    protected function resolveHostForRelativeRequest(string $uri): ?string
    {
        $path = '/' . ltrim(parse_url($uri, PHP_URL_PATH) ?: $uri, '/');

        if ($this->isApexRelativePath($path)) {
            return $this->registeredApexHost();
        }

        $user = auth('web')->user();
        if ($user instanceof Authenticatable) {
            $tenantId = (int) data_get($user, 'tenant_id', 0);

            if ($tenantId === 1 && $this->isPlatformSuperAdmin($user)) {
                return config('multitenancy.platform_admin_subdomain', 'dash') . '.' . $this->rootDomain();
            }

            if ($tenantId > 0) {
                $user->loadMissing('tenant');
                $slug = (string) optional($user->tenant)->slug;
                if ($slug !== '') {
                    return $slug . '.' . $this->rootDomain();
                }
            }
        }

        $account = (string) (URL::getDefaultParameters()['account'] ?? '');
        if ($account !== '') {
            return $account . '.' . $this->rootDomain();
        }

        return config('multitenancy.platform_admin_subdomain', 'dash') . '.' . $this->rootDomain();
    }

    protected function isApexRelativePath(string $path): bool
    {
        foreach ([
            '/',
            '/login',
            '/register',
            '/forgot-password',
            '/reset-password',
            '/verify-email',
            '/confirm-password',
            '/health',
            '/meetra',
            '/products',
            '/contact-us',
            '/omnichannel',
            '/accounting',
            '/mulai-digital',
            '/website-aplikasi-bisnis',
            '/jasa-pembuatan-website',
            '/affiliate-program',
            '/aff/',
            '/workspace',
            '/tentang-kami',
            '/keamanan-data',
            '/kebijakan-privasi',
            '/syarat-ketentuan',
            '/onboarding',
            '/platform/billing/',
            '/platform/public/',
            '/locale/switch',
            '/webhooks/utas',
        ] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected function rootDomain(): string
    {
        foreach (app('router')->getRoutes() as $route) {
            $domain = (string) ($route->getDomain() ?? '');

            if (str_starts_with($domain, '{account}.')) {
                return substr($domain, strlen('{account}.'));
            }
        }

        return SaasHost::candidateRootDomains()[0] ?? 'example.test';
    }

    protected function isAbsoluteUrl(string $uri): bool
    {
        return str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://');
    }

    protected function registeredApexHost(): string
    {
        foreach (app('router')->getRoutes() as $route) {
            if ($route->getDomain()) {
                continue;
            }

            if ($route->uri() === 'onboarding' || $route->uri() === '/' || $route->uri() === 'products') {
                return (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: $this->rootDomain());
            }
        }

        return (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: $this->rootDomain());
    }

    protected function isPlatformSuperAdmin(Authenticatable $user): bool
    {
        if (!method_exists($user, 'hasRole') || (int) data_get($user, 'tenant_id', 0) !== 1) {
            return false;
        }

        if ($user->hasRole('Super-admin')) {
            return true;
        }

        if (!method_exists($user, 'roles')) {
            return false;
        }

        return $user->roles()
            ->where('name', 'Super-admin')
            ->where(function ($query): void {
                $query->whereNull('roles.tenant_id')
                    ->orWhere('roles.tenant_id', 1);
            })
            ->exists();
    }
}
