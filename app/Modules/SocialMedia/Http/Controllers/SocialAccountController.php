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
use Illuminate\Support\Collection;
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
        ]);
    }

    public function update(SocialAccountRequest $request, SocialAccount $account): RedirectResponse
    {
        $data = $request->validated();
        unset($data['auto_reply'], $data['chatbot_account_id']);

        $account->update($data);
        $this->persistChatbotIntegration($request, $account);

        return redirect()->route('social-media.accounts.index')->with('status', 'Akun diperbarui.');
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
}
