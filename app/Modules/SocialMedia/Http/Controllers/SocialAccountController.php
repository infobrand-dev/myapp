<?php

namespace App\Modules\SocialMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SocialMedia\Http\Requests\SocialAccountRequest;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\Chatbot\Models\ChatbotAccount;
use Illuminate\Http\RedirectResponse;
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
        $chatbotAccounts = ChatbotAccount::where('status', 'active')->orderBy('name')->get();
        return view('socialmedia::accounts.form', compact('account', 'chatbotAccounts'));
    }

    public function store(SocialAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['auto_reply'] = $request->boolean('auto_reply');
        $data['created_by'] = $request->user()?->id;
        SocialAccount::create($data);

        return redirect()->route('social-media.accounts.index')->with('status', 'Akun ditambahkan.');
    }

    public function edit(SocialAccount $account): View
    {
        $chatbotAccounts = ChatbotAccount::where('status', 'active')->orderBy('name')->get();
        return view('socialmedia::accounts.form', compact('account', 'chatbotAccounts'));
    }

    public function update(SocialAccountRequest $request, SocialAccount $account): RedirectResponse
    {
        $data = $request->validated();
        $data['auto_reply'] = $request->boolean('auto_reply');
        $account->update($data);
        return redirect()->route('social-media.accounts.index')->with('status', 'Akun diperbarui.');
    }

    public function destroy(SocialAccount $account): RedirectResponse
    {
        $account->delete();
        return back()->with('status', 'Akun dihapus.');
    }
}
