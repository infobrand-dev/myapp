<?php

namespace App\Modules\Chatbot\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chatbot\Models\ChatbotAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatbotAccountController extends Controller
{
    public function index(): View
    {
        $accounts = ChatbotAccount::orderBy('name')->paginate(20);
        return view('chatbot::accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        $account = new ChatbotAccount(['provider' => 'openai', 'status' => 'active', 'model' => 'gpt-4o-mini']);
        return view('chatbot::accounts.form', compact('account'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()?->id;
        ChatbotAccount::create($data);
        return redirect()->route('chatbot.accounts.index')->with('status', 'Chatbot account dibuat.');
    }

    public function edit(ChatbotAccount $account): View
    {
        return view('chatbot::accounts.form', ['account' => $account]);
    }

    public function update(Request $request, ChatbotAccount $account): RedirectResponse
    {
        $account->update($this->validated($request));
        return redirect()->route('chatbot.accounts.index')->with('status', 'Chatbot account diperbarui.');
    }

    public function destroy(ChatbotAccount $account): RedirectResponse
    {
        $account->delete();
        return back()->with('status', 'Chatbot account dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'in:openai'],
            'model' => ['nullable', 'string', 'max:255'],
            'api_key' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'metadata' => ['nullable'],
        ]);
    }
}
