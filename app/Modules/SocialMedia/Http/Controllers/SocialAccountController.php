<?php

namespace App\Modules\SocialMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SocialMedia\Http\Requests\SocialAccountRequest;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\SocialMedia\Models\SocialAccountChatbotIntegration;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SocialAccountController extends Controller
{
    public function index(): View
    {
        $accounts = SocialAccount::query()
            ->where('tenant_id', TenantContext::currentId())
            ->orderBy('platform')
            ->orderBy('name')
            ->paginate(20);

        return view('socialmedia::accounts.index', [
            'accounts' => $accounts,
            'metaOAuthReady' => $this->metaOauthReady(),
            'xOAuthReady' => $this->xOauthReady(),
            'xTenantBetaEnabled' => $this->xTenantBetaEnabled(),
        ]);
    }

    public function edit(SocialAccount $account): View
    {
        return view('socialmedia::accounts.form', [
            'account' => $account,
            'chatbotAccounts' => $this->chatbotAccounts(),
            'integration' => $account->chatbotIntegration()->first(),
            'chatbotEnabled' => $this->isChatbotModuleReady(),
            'metaOAuthReady' => $this->metaOauthReady(),
            'xOAuthReady' => $this->xOauthReady(),
            'xTenantBetaEnabled' => $this->xTenantBetaEnabled(),
            'internalCreateMode' => false,
            'xCreateMode' => 'edit',
        ]);
    }

    public function testConnection(SocialAccount $account): RedirectResponse
    {
        if ($account->platform !== 'x') {
            return back()->withErrors([
                'connection_test' => 'Test connection saat ini hanya tersedia untuk akun X.',
            ]);
        }

        $token = trim((string) $account->access_token);
        if ($token === '') {
            $account->updateOperationalMetadata([
                'last_connection_tested_at' => now()->toDateTimeString(),
                'last_connection_test_status' => 'error',
                'last_connection_test_message' => 'Token OAuth X belum tersedia.',
            ]);

            return back()->withErrors([
                'connection_test' => 'Token OAuth X belum tersedia. Hubungkan ulang akun X.',
            ]);
        }

        $client = app(\App\Modules\SocialMedia\Services\XDirectMessageClient::class);
        $tokenManager = app(\App\Modules\SocialMedia\Services\XTokenManager::class);

        $response = $client->fetchAuthenticatedUser($token);
        if (in_array($response->status(), [401, 403], true) && $tokenManager->canRefresh($account)) {
            $refreshedToken = $tokenManager->refreshAccessToken($account);
            if ($refreshedToken) {
                $response = $client->fetchAuthenticatedUser($refreshedToken);
            }
        }

        if (!$response->successful()) {
            $message = trim((string) $response->body()) ?: 'Koneksi X gagal diuji.';
            $account->updateOperationalMetadata([
                'last_connection_tested_at' => now()->toDateTimeString(),
                'last_connection_test_status' => 'error',
                'last_connection_test_message' => mb_substr($message, 0, 500),
            ]);

            return back()->withErrors([
                'connection_test' => 'Koneksi X gagal diuji. ' . $message,
            ]);
        }

        $profile = (array) $response->json('data', []);
        $account->updateOperationalMetadata([
            'last_connection_tested_at' => now()->toDateTimeString(),
            'last_connection_test_status' => 'ok',
            'last_connection_test_message' => 'Terhubung sebagai @' . trim((string) data_get($profile, 'username', '')),
            'x_connector_status' => 'active',
        ]);

        return back()->with('status', 'Koneksi X berhasil diuji.');
    }

    public function update(SocialAccountRequest $request, SocialAccount $account): RedirectResponse
    {
        $data = $request->validated();
        unset($data['auto_reply'], $data['chatbot_account_id']);
        $metadata = is_array($account->metadata) ? $account->metadata : [];

        if ($account->platform === 'x') {
            $metadata['x_connector_status'] = trim((string) ($data['x_connector_status'] ?? '')) ?: 'not_configured';
            $data['metadata'] = $metadata;
        }

        $account->update($data);
        $this->persistChatbotIntegration($request, $account);

        return redirect()->route('social-media.accounts.index')->with('status', 'Akun diperbarui.');
    }

    public function createXInternal(): View
    {
        abort_unless($this->xInternalEnabled() && $this->isPlatformAdminHost(), Response::HTTP_NOT_FOUND);

        return $this->xConfigForm('internal');
    }

    public function storeXInternal(SocialAccountRequest $request): RedirectResponse
    {
        abort_unless($this->xInternalEnabled() && $this->isPlatformAdminHost(), Response::HTTP_NOT_FOUND);

        return $this->storeXConfiguredAccount($request, 'internal');
    }

    public function redirectToX(Request $request): RedirectResponse
    {
        abort_unless($this->xTenantBetaEnabled(), Response::HTTP_NOT_FOUND);

        if (!$this->xOauthReady()) {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors([
                    'x_oauth' => 'X OAuth belum siap. Isi X_API_CLIENT_ID dan X_API_CLIENT_SECRET di environment platform.',
                ]);
        }

        $state = Str::random(40);
        $verifier = Str::random(96);
        $request->session()->put('social_media.x_oauth_state', $state);
        $request->session()->put('social_media.x_oauth_tenant_id', TenantContext::currentId());
        $request->session()->put('social_media.x_oauth_code_verifier', $verifier);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.x_api.client_id'),
            'redirect_uri' => route('social-media.accounts.connect.x.callback'),
            'scope' => implode(' ', config('services.x_api.oauth_scopes', [])),
            'state' => $state,
            'code_challenge' => $this->pkceChallenge($verifier),
            'code_challenge_method' => 'S256',
        ]);

        return redirect()->away(rtrim((string) config('services.x_api.authorize_url'), '?') . '?' . $query);
    }

    public function handleXCallback(Request $request): RedirectResponse
    {
        abort_unless($this->xTenantBetaEnabled(), Response::HTTP_NOT_FOUND);

        $expectedState = (string) $request->session()->pull('social_media.x_oauth_state');
        $expectedTenantId = (int) $request->session()->pull('social_media.x_oauth_tenant_id');
        $verifier = (string) $request->session()->pull('social_media.x_oauth_code_verifier');

        if ($expectedState === '' || !hash_equals($expectedState, (string) $request->query('state', ''))) {
            abort(419, 'OAuth state mismatch.');
        }

        if ($expectedTenantId > 0 && $expectedTenantId !== TenantContext::currentId()) {
            abort(403, 'Tenant context mismatch for X connect.');
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors([
                    'x_oauth' => (string) $request->query('error_description', 'Koneksi X dibatalkan.'),
                ]);
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '' || $verifier === '') {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors(['x_oauth' => 'X tidak mengembalikan authorization code yang valid.']);
        }

        $tokenRequest = Http::asForm()
            ->timeout(20)
            ->withBasicAuth((string) config('services.x_api.client_id'), (string) config('services.x_api.client_secret'))
            ->post((string) config('services.x_api.token_url'), [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('social-media.accounts.connect.x.callback'),
                'code_verifier' => $verifier,
            ]);

        if (!$tokenRequest->successful()) {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors(['x_oauth' => 'Gagal menukar authorization code X menjadi access token.']);
        }

        $accessToken = trim((string) $tokenRequest->json('access_token'));
        if ($accessToken === '') {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors(['x_oauth' => 'X tidak mengembalikan access token yang valid.']);
        }

        $profileRequest = Http::withToken($accessToken)
            ->timeout(20)
            ->get(rtrim((string) config('services.x_api.base_url'), '/') . '/2/users/me', [
                'user.fields' => 'username,name,profile_image_url,verified',
            ]);

        if (!$profileRequest->successful()) {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors(['x_oauth' => 'Gagal mengambil profil akun X yang terhubung.']);
        }

        $profile = (array) $profileRequest->json('data', []);
        $xUserId = trim((string) data_get($profile, 'id', ''));
        if ($xUserId === '') {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors(['x_oauth' => 'Profil X yang terhubung tidak memiliki user ID yang valid.']);
        }

        $account = $this->upsertXOAuthAccount(
            $xUserId,
            $accessToken,
            trim((string) $tokenRequest->json('refresh_token', '')),
            (array) $profile,
            $request->user()?->id
        );

        return redirect()
            ->route('social-media.accounts.edit', $account)
            ->with('status', 'Akun X berhasil dihubungkan.');
    }

    public function destroy(SocialAccount $account): RedirectResponse
    {
        $account->delete();

        return back()->with('status', 'Akun dihapus.');
    }

    public function redirectToMeta(Request $request): RedirectResponse
    {
        if (!$this->metaOauthReady()) {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors([
                    'meta_oauth' => 'META_APP_ID dan META_APP_SECRET wajib diisi di environment platform sebelum tenant dapat connect akun sosial media.',
                ]);
        }

        $state = Str::random(40);
        $request->session()->put('social_media.oauth_state', $state);
        $request->session()->put('social_media.oauth_tenant_id', TenantContext::currentId());

        $query = http_build_query([
            'client_id' => config('services.meta.app_id'),
            'redirect_uri' => route('social-media.accounts.connect.meta.callback'),
            'response_type' => 'code',
            'scope' => implode(',', config('services.meta.oauth_scopes', [])),
            'state' => $state,
        ]);

        return redirect()->away('https://www.facebook.com/' . config('services.meta.graph_version', 'v22.0') . '/dialog/oauth?' . $query);
    }

    public function handleMetaCallback(Request $request): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('social_media.oauth_state');
        $expectedTenantId = (int) $request->session()->pull('social_media.oauth_tenant_id');

        if ($expectedState === '' || !hash_equals($expectedState, (string) $request->query('state', ''))) {
            abort(419, 'OAuth state mismatch.');
        }

        if ($expectedTenantId > 0 && $expectedTenantId !== TenantContext::currentId()) {
            abort(403, 'Tenant context mismatch for Meta connect.');
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors([
                    'meta_oauth' => (string) $request->query('error_message', 'Meta connection was cancelled.'),
                ]);
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors(['meta_oauth' => 'Meta tidak mengembalikan authorization code.']);
        }

        $graphVersion = config('services.meta.graph_version', 'v22.0');
        $accessTokenResponse = Http::timeout(20)->get("https://graph.facebook.com/{$graphVersion}/oauth/access_token", [
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'redirect_uri' => route('social-media.accounts.connect.meta.callback'),
            'code' => $code,
        ]);

        if (!$accessTokenResponse->successful()) {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors(['meta_oauth' => 'Gagal menukar authorization code Meta menjadi access token.']);
        }

        $userAccessToken = trim((string) $accessTokenResponse->json('access_token'));
        if ($userAccessToken === '') {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors(['meta_oauth' => 'Meta tidak mengembalikan access token yang valid.']);
        }

        $pagesResponse = Http::withToken($userAccessToken)
            ->timeout(20)
            ->get("https://graph.facebook.com/{$graphVersion}/me/accounts", [
                'fields' => 'id,name,access_token,instagram_business_account{id,username,name},picture{url}',
                'limit' => 200,
            ]);

        if (!$pagesResponse->successful()) {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors(['meta_oauth' => 'Gagal mengambil daftar page/akun sosial media dari Meta.']);
        }

        $pages = collect($pagesResponse->json('data', []))
            ->filter(fn ($page) => is_array($page) && !empty($page['id']) && !empty($page['access_token']));

        if ($pages->isEmpty()) {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors(['meta_oauth' => 'Tidak ada Facebook Page atau Instagram Business Account yang berhasil dihubungkan.']);
        }

        $newAccountCount = $this->estimateMetaAccountsToCreate($pages);
        if ($newAccountCount > 0) {
            app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::SOCIAL_ACCOUNTS, $newAccountCount);
        }

        $connected = $this->syncMetaAccounts($pages, $request->user()?->id);

        if ($connected === 0) {
            return redirect()
                ->route('social-media.accounts.index')
                ->withErrors(['meta_oauth' => 'Akun Meta terhubung, tetapi belum ada Page/Instagram Business Account yang siap dipakai.']);
        }

        return redirect()
            ->route('social-media.accounts.index')
            ->with('status', "{$connected} akun sosial media berhasil dihubungkan melalui Meta OAuth.");
    }

    private function syncMetaAccounts(Collection $pages, ?int $userId): int
    {
        $connected = 0;

        foreach ($pages as $page) {
            $connected += $this->syncFacebookPageAccount((array) $page, $userId);
            $connected += $this->syncInstagramBusinessAccount((array) $page, $userId);
        }

        return $connected;
    }

    private function estimateMetaAccountsToCreate(Collection $pages): int
    {
        $tenantId = TenantContext::currentId();
        $newAccounts = 0;

        foreach ($pages as $page) {
            $pageId = trim((string) data_get($page, 'id', ''));
            if ($pageId !== '' && !SocialAccount::query()
                ->where('tenant_id', $tenantId)
                ->where('platform', 'facebook')
                ->where('page_id', $pageId)
                ->exists()) {
                $newAccounts++;
            }

            $instagramId = trim((string) data_get($page, 'instagram_business_account.id', ''));
            if ($instagramId !== '' && !SocialAccount::query()
                ->where('tenant_id', $tenantId)
                ->where('platform', 'instagram')
                ->where('ig_business_id', $instagramId)
                ->exists()) {
                $newAccounts++;
            }
        }

        return $newAccounts;
    }

    private function syncFacebookPageAccount(array $page, ?int $userId): int
    {
        SocialAccount::query()->updateOrCreate(
            [
                'tenant_id' => TenantContext::currentId(),
                'platform' => 'facebook',
                'page_id' => (string) $page['id'],
            ],
            [
                'ig_business_id' => null,
                'access_token' => (string) $page['access_token'],
                'name' => (string) ($page['name'] ?? 'Facebook Page'),
                'status' => 'active',
                'metadata' => array_filter([
                    'connection_source' => 'meta_oauth',
                    'page_picture_url' => data_get($page, 'picture.data.url'),
                    'oauth_connected_at' => now()->toDateTimeString(),
                ]),
                'created_by' => $userId,
            ]
        );

        return 1;
    }

    private function syncInstagramBusinessAccount(array $page, ?int $userId): int
    {
        $instagram = data_get($page, 'instagram_business_account');
        $instagramId = trim((string) data_get($instagram, 'id', ''));
        if ($instagramId === '') {
            return 0;
        }

        SocialAccount::query()->updateOrCreate(
            [
                'tenant_id' => TenantContext::currentId(),
                'platform' => 'instagram',
                'ig_business_id' => $instagramId,
            ],
            [
                'page_id' => (string) $page['id'],
                'access_token' => (string) $page['access_token'],
                'name' => (string) (data_get($instagram, 'name') ?: data_get($instagram, 'username') ?: data_get($page, 'name', 'Instagram Account')),
                'status' => 'active',
                'metadata' => array_filter([
                    'connection_source' => 'meta_oauth',
                    'instagram_username' => data_get($instagram, 'username'),
                    'facebook_page_name' => data_get($page, 'name'),
                    'oauth_connected_at' => now()->toDateTimeString(),
                ]),
                'created_by' => $userId,
            ]
        );

        return 1;
    }

    private function persistChatbotIntegration(SocialAccountRequest $request, SocialAccount $account): void
    {
        if (!Schema::hasTable('social_account_chatbot_integrations')) {
            return;
        }

        $autoReply = $request->boolean('auto_reply');
        $chatbotAccountId = $request->filled('chatbot_account_id')
            ? (int) $request->input('chatbot_account_id')
            : null;

        if (!$this->isChatbotModuleReady()) {
            $autoReply = false;
            $chatbotAccountId = null;
        }

        $chatbotAccountId = $this->resolveChannelReadyChatbotAccountId($chatbotAccountId);

        if (!$autoReply && !$chatbotAccountId) {
            SocialAccountChatbotIntegration::query()
                ->where('social_account_id', $account->id)
                ->delete();

            return;
        }

        SocialAccountChatbotIntegration::query()->updateOrCreate(
            ['social_account_id' => $account->id],
            [
                'auto_reply' => $autoReply,
                'chatbot_account_id' => $chatbotAccountId,
            ]
        );
    }

    private function isChatbotModuleReady(): bool
    {
        return class_exists(\App\Modules\Chatbot\Models\ChatbotAccount::class)
            && Schema::hasTable('chatbot_accounts');
    }

    private function chatbotAccounts()
    {
        if (!$this->isChatbotModuleReady()) {
            return collect();
        }

        $chatbotClass = \App\Modules\Chatbot\Models\ChatbotAccount::class;
        $hasAccessScope = Schema::hasColumn('chatbot_accounts', 'access_scope');

        return $chatbotClass::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('status', 'active')
            ->when($hasAccessScope, fn ($query) => $query->where('access_scope', 'public'))
            ->orderBy('name')
            ->get();
    }

    private function resolveChannelReadyChatbotAccountId(?int $chatbotAccountId): ?int
    {
        if (!$chatbotAccountId || !$this->isChatbotModuleReady()) {
            return null;
        }

        $chatbotClass = \App\Modules\Chatbot\Models\ChatbotAccount::class;
        $hasAccessScope = Schema::hasColumn('chatbot_accounts', 'access_scope');
        $account = $chatbotClass::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('status', 'active')
            ->when($hasAccessScope, fn ($query) => $query->where('access_scope', 'public'))
            ->find($chatbotAccountId);

        if (!$account) {
            throw ValidationException::withMessages([
                'chatbot_account_id' => 'Chatbot private tidak bisa dihubungkan ke channel.',
            ]);
        }

        return (int) $account->id;
    }

    private function metaOauthReady(): bool
    {
        return filled(config('services.meta.app_id')) && filled(config('services.meta.app_secret'));
    }

    private function xOauthReady(): bool
    {
        return filled(config('services.x_api.client_id')) && filled(config('services.x_api.client_secret'));
    }

    private function xInternalEnabled(): bool
    {
        return (bool) config('services.x_api.internal_enabled', false);
    }

    private function isPlatformAdminHost(): bool
    {
        return (bool) request()->attributes->get('platform_admin_host', false);
    }

    private function xTenantBetaEnabled(): bool
    {
        return (bool) config('services.x_api.tenant_beta_enabled', true);
    }

    private function xConfigForm(string $mode): View
    {
        $account = new SocialAccount([
            'platform' => 'x',
            'status' => 'inactive',
            'metadata' => [
                'x_connector_status' => 'not_configured',
            ],
        ]);

        return view('socialmedia::accounts.form', [
            'account' => $account,
            'chatbotAccounts' => $this->chatbotAccounts(),
            'integration' => null,
            'chatbotEnabled' => $this->isChatbotModuleReady(),
            'metaOAuthReady' => $this->metaOauthReady(),
            'xOAuthReady' => $this->xOauthReady(),
            'xTenantBetaEnabled' => $this->xTenantBetaEnabled(),
            'internalCreateMode' => true,
            'xCreateMode' => 'internal',
        ]);
    }

    private function storeXConfiguredAccount(SocialAccountRequest $request, string $mode): RedirectResponse
    {
        app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::SOCIAL_ACCOUNTS, 1);

        $data = $request->validated();

        $account = SocialAccount::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'platform' => 'x',
            'name' => trim((string) ($data['name'] ?? '')) ?: 'X Account',
            'status' => trim((string) ($data['status'] ?? 'inactive')) ?: 'inactive',
            'access_token' => trim((string) ($data['access_token'] ?? '')),
            'metadata' => [
                'connection_source' => $mode === 'internal' ? 'internal_x_config' : 'tenant_x_beta',
                'x_user_id' => trim((string) ($data['x_user_id'] ?? '')) ?: null,
                'x_handle' => trim((string) ($data['x_handle'] ?? '')) ?: null,
                'x_connector_status' => trim((string) ($data['x_connector_status'] ?? '')) ?: 'not_configured',
            ],
            'created_by' => $request->user()?->id,
        ]);

        $this->persistChatbotIntegration($request, $account);

        $message = $mode === 'internal'
            ? 'Akun internal X berhasil dibuat.'
            : 'Akun X beta berhasil dibuat.';

        return redirect()->route('social-media.accounts.edit', $account)
            ->with('status', $message);
    }

    private function upsertXOAuthAccount(string $xUserId, string $accessToken, string $refreshToken, array $profile, ?int $userId): SocialAccount
    {
        $tenantId = TenantContext::currentId();
        $existing = SocialAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('platform', 'x')
            ->get()
            ->first(function (SocialAccount $account) use ($xUserId) {
                return trim((string) data_get($account->metadata, 'x_user_id', '')) === $xUserId;
            });

        if (!$existing) {
            app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::SOCIAL_ACCOUNTS, 1);
            $existing = new SocialAccount([
                'tenant_id' => $tenantId,
                'platform' => 'x',
                'created_by' => $userId,
            ]);
        }

        $metadata = is_array($existing->metadata) ? $existing->metadata : [];
        $metadata['connection_source'] = 'x_oauth';
        $metadata['x_user_id'] = $xUserId;
        $metadata['x_handle'] = trim((string) data_get($profile, 'username', '')) ?: null;
        $metadata['x_connector_status'] = 'active';
        $metadata['oauth_connected_at'] = now()->toDateTimeString();
        $metadata['x_profile_image_url'] = trim((string) data_get($profile, 'profile_image_url', '')) ?: null;
        $metadata['x_verified'] = (bool) data_get($profile, 'verified', false);
        $metadata['x_refresh_token_enc'] = $refreshToken !== '' ? Crypt::encryptString($refreshToken) : data_get($metadata, 'x_refresh_token_enc');
        $name = trim((string) data_get($profile, 'name', ''));
        $username = trim((string) data_get($profile, 'username', ''));

        $existing->fill([
            'name' => $name !== '' ? $name : ($username !== '' ? '@' . $username : 'X Account'),
            'status' => 'active',
            'access_token' => $accessToken,
            'metadata' => $metadata,
        ]);
        $existing->save();

        return $existing;
    }

    private function pkceChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }
}
