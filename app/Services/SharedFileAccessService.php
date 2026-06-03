<?php

namespace App\Services;

use App\Models\StoredFile;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SharedFileAccessService
{
    public function __construct(
        private readonly StoredFileService $storedFiles
    ) {
    }

    /**
     * @return array{url:string,expires_at:\Illuminate\Support\Carbon,audience:string}
     */
    public function issueShareUrl(
        StoredFile $storedFile,
        string $audience = 'provider',
        ?int $ttlSeconds = null,
        ?Authenticatable $issuer = null,
        array $meta = []
    ): array {
        $ttlSeconds ??= (int) config("workspace-files.categories.{$storedFile->category}.share_ttl_seconds", 900);
        $expiresAt = now()->addSeconds(max(60, $ttlSeconds));

        $url = URL::temporarySignedRoute('stored-files.share', $expiresAt, [
            'storedFileId' => $storedFile->id,
            'audience' => $audience,
        ]);

        $this->storedFiles->logAccessFromContext($storedFile, 'signed_url_issued', true, array_merge([
            'category' => $storedFile->category,
            'access_class' => $storedFile->access_class,
            'audience' => $audience,
            'expires_at' => $expiresAt->toIso8601String(),
            'issued_by' => $issuer?->getAuthIdentifier(),
        ], $meta));

        return [
            'url' => $url,
            'expires_at' => $expiresAt,
            'audience' => $audience,
        ];
    }

    public function sharedResponse(Request $request, int $storedFileId): Response
    {
        $storedFile = StoredFile::query()->findOrFail($storedFileId);
        $audience = trim((string) $request->query('audience', 'provider')) ?: 'provider';

        $result = $this->storedFiles->previewResponse($storedFile, $storedFile->original_name);

        $this->storedFiles->logAccess($storedFile, $request, $audience === 'provider' ? 'provider_fetch' : 'signed_url_accessed', true, [
            'category' => $storedFile->category,
            'access_class' => $storedFile->access_class,
            'audience' => $audience,
            'result' => $result['result'],
            'http_status' => $result['response']->getStatusCode(),
        ]);

        return $result['response'];
    }
}
