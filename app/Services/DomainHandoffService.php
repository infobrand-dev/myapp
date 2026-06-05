<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

class DomainHandoffService
{
    public function __construct(
        private readonly CacheRepository $cache,
    ) {
    }

    public function issue(User $user, string $targetHost, string $targetPath): string
    {
        $token = Str::random(64);

        $this->cache->put($this->cacheKey($token), [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'target_path' => $targetPath,
        ], now()->addMinutes(2));

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $originalRoot = (string) config('app.url');

        URL::forceRootUrl($scheme . '://' . $targetHost);

        try {
            return URL::temporarySignedRoute('tenant.domain-handoff.consume', now()->addMinutes(2), [
                'token' => $token,
            ]);
        } finally {
            URL::forceRootUrl($originalRoot !== '' ? $originalRoot : null);
        }
    }

    /**
     * @return array{user_id:int,tenant_id:int,target_path:string}
     */
    public function consume(string $token): array
    {
        $payload = $this->cache->pull($this->cacheKey($token));

        if (!is_array($payload) || empty($payload['user_id']) || empty($payload['target_path'])) {
            throw new RuntimeException('Token handoff domain sudah tidak valid.');
        }

        return [
            'user_id' => (int) $payload['user_id'],
            'tenant_id' => (int) ($payload['tenant_id'] ?? 0),
            'target_path' => (string) $payload['target_path'],
        ];
    }

    public function intendedPath(Request $request): string
    {
        $uri = $request->getRequestUri();

        return str_starts_with($uri, '/domain-handoff/') ? '/dashboard' : $uri;
    }

    private function cacheKey(string $token): string
    {
        return 'tenant-domain-handoff:' . $token;
    }
}
