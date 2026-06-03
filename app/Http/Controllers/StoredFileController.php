<?php

namespace App\Http\Controllers;

use App\Models\StoredFile;
use App\Services\SharedFileAccessService;
use App\Services\StorageAccessService;
use App\Services\StoredFileService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoredFileController extends Controller
{
    public function download(Request $request, StoredFile $storedFile, StoredFileService $files): Response
    {
        $authorized = (int) $storedFile->tenant_id === (int) TenantContext::currentId()
            && ($storedFile->company_id === null || (int) $storedFile->company_id === (int) CompanyContext::currentId())
            && ($storedFile->branch_id === null || (int) $storedFile->branch_id === (int) BranchContext::currentId());

        if (!$authorized || !$files->userCanAccess($storedFile, $request->user())) {
            $files->logAccess($storedFile, $request, 'download', false, [
                'category' => $storedFile->category,
                'result' => 'forbidden',
            ]);

            abort(403);
        }

        $result = $files->downloadResponse($storedFile);

        $files->logAccess($storedFile, $request, 'download', true, [
            'category' => $storedFile->category,
            'result' => $result['result'],
            'http_status' => $result['response']->getStatusCode(),
        ]);

        return $result['response'];
    }

    public function preview(Request $request, StoredFile $storedFile, StoredFileService $files): Response
    {
        $authorized = (int) $storedFile->tenant_id === (int) TenantContext::currentId()
            && ($storedFile->company_id === null || (int) $storedFile->company_id === (int) CompanyContext::currentId())
            && ($storedFile->branch_id === null || (int) $storedFile->branch_id === (int) BranchContext::currentId());

        if (!$authorized || !$files->userCanAccess($storedFile, $request->user())) {
            $files->logAccess($storedFile, $request, 'preview', false, [
                'category' => $storedFile->category,
                'result' => 'forbidden',
            ]);

            abort(403);
        }

        $result = $files->previewResponse($storedFile);
        $files->logAccess($storedFile, $request, 'preview', true, [
            'category' => $storedFile->category,
            'result' => $result['result'],
            'http_status' => $result['response']->getStatusCode(),
        ]);

        return $result['response'];
    }

    public function legacyDownload(Request $request, StorageAccessService $access): Response
    {
        abort_unless((int) $request->user()?->id === (int) $request->query('issued_to'), 403);

        $category = trim((string) $request->query('category', ''));
        $path = trim((string) $request->query('path', ''));
        $disk = trim((string) $request->query('disk', 'private'));
        $name = trim((string) $request->query('name', ''));

        abort_unless($path !== '' && $category !== '', 404);

        $permissions = (array) config("workspace-files.categories.{$category}.permissions", []);
        if ($permissions !== [] && method_exists($request->user(), 'hasAnyPermission') && !$request->user()->hasAnyPermission($permissions)) {
            abort(403);
        }

        $result = $access->download(app(StoredFileService::class)->ensureLegacyReference($disk, $path, $category, [
            'source_module' => 'legacy',
            'source_context' => 'legacy_download',
            'original_name' => $name !== '' ? $name : basename($path),
        ]), $name !== '' ? $name : null);

        return $result['response'];
    }

    public function share(Request $request, int $storedFileId, SharedFileAccessService $sharedFiles): Response
    {
        return $sharedFiles->sharedResponse($request, $storedFileId);
    }
}
