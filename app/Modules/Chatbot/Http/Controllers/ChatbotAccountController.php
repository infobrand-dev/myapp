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
        $account = new ChatbotAccount([
            'provider' => 'openai',
            'status' => 'active',
            'model' => 'gpt-4o-mini',
            'response_style' => 'balanced',
            'rag_top_k' => 3,
        ]);
        return view('chatbot::accounts.form', compact('account'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $user = $request->user();
        $data['created_by'] = $user ? $user->id : null;
        $data['mirror_to_conversations'] = $request->boolean('mirror_to_conversations');
        $data['rag_enabled'] = $request->boolean('rag_enabled');
        ChatbotAccount::create($data);
        return redirect()->route('chatbot.accounts.index')->with('status', 'Chatbot account dibuat.');
    }

    public function edit(ChatbotAccount $account): View
    {
        return view('chatbot::accounts.form', ['account' => $account]);
    }

    public function update(Request $request, ChatbotAccount $account): RedirectResponse
    {
        $data = $this->validated($request, true);
        $data['mirror_to_conversations'] = $request->boolean('mirror_to_conversations');
        $data['rag_enabled'] = $request->boolean('rag_enabled');
        $account->update($data);
        return redirect()->route('chatbot.accounts.index')->with('status', 'Chatbot account diperbarui.');
    }

    public function destroy(ChatbotAccount $account): RedirectResponse
    {
        $account->delete();
        return back()->with('status', 'Chatbot account dihapus.');
    }

    private function validated(Request $request, bool $isEdit = false): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'in:openai'],
            'model' => ['nullable', 'string', 'max:255'],
            'system_prompt' => ['nullable', 'string', 'max:10000'],
            'focus_scope' => ['nullable', 'string', 'max:10000'],
            'response_style' => ['required', 'in:concise,balanced,detailed'],
            'api_key' => [$isEdit ? 'nullable' : 'required', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'mirror_to_conversations' => ['sometimes', 'boolean'],
            'rag_enabled' => ['sometimes', 'boolean'],
            'rag_top_k' => ['nullable', 'integer', 'min:1', 'max:8'],
            'metadata' => ['nullable'],
        ]);

        if ($isEdit && !$request->filled('api_key')) {
            unset($data['api_key']);
        }

        return $data;
    }
}
