<?php

namespace App\Modules\SocialMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SocialMedia\Http\Requests\SocialAccountRequest;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\SocialMedia\Models\SocialAccountChatbotIntegration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SocialAccountController extends Controller
{
    public function index(): View
    {
        $accounts = SocialAccount::orderBy('platform')->orderBy('name')->paginate(20);
        return view('socialmedia::accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        $account = new SocialAccount(['status' => 'active', 'platform' => 'facebook']);
        $chatbotAccounts = $this->chatbotAccounts();
        $integration = null;
        $chatbotEnabled = $this->isChatbotModuleReady();
        return view('socialmedia::accounts.form', compact('account', 'chatbotAccounts', 'integration', 'chatbotEnabled'));
    }

    public function store(SocialAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()?->id;
        unset($data['auto_reply'], $data['chatbot_account_id']);
        $account = SocialAccount::create($data);
        $this->persistChatbotIntegration($request, $account);

        return redirect()->route('social-media.accounts.index')->with('status', 'Akun ditambahkan.');
    }

    public function edit(SocialAccount $account): View
    {
        $chatbotAccounts = $this->chatbotAccounts();
        $integration = $account->chatbotIntegration()->first();
        $chatbotEnabled = $this->isChatbotModuleReady();
        return view('socialmedia::accounts.form', compact('account', 'chatbotAccounts', 'integration', 'chatbotEnabled'));
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
        return $chatbotClass::query()->where('status', 'active')->orderBy('name')->get();
    }
}
