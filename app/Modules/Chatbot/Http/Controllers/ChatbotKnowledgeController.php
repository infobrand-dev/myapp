<?php

namespace App\Modules\Chatbot\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotKnowledgeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatbotKnowledgeController extends Controller
{
    public function index(ChatbotAccount $account): View
    {
        $documents = ChatbotKnowledgeDocument::query()
            ->where('chatbot_account_id', $account->id)
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('chatbot::knowledge.index', compact('account', 'documents'));
    }

    public function create(ChatbotAccount $account): View
    {
        $document = new ChatbotKnowledgeDocument();
        return view('chatbot::knowledge.form', compact('account', 'document'));
    }

    public function store(Request $request, ChatbotAccount $account): RedirectResponse
    {
        $data = $this->validated($request);
        $document = ChatbotKnowledgeDocument::query()->create([
            'chatbot_account_id' => $account->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'metadata' => [
                'source' => $data['source'] ?? 'manual',
            ],
        ]);

        $this->rebuildChunks($document, (int) ($data['chunk_size'] ?? 600));

        return redirect()
            ->route('chatbot.knowledge.index', $account)
            ->with('status', 'Dokumen knowledge ditambahkan.');
    }

    public function edit(ChatbotAccount $account, ChatbotKnowledgeDocument $document): View
    {
        $this->assertOwnership($account, $document);
        return view('chatbot::knowledge.form', compact('account', 'document'));
    }

    public function update(Request $request, ChatbotAccount $account, ChatbotKnowledgeDocument $document): RedirectResponse
    {
        $this->assertOwnership($account, $document);
        $data = $this->validated($request);

        $document->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'metadata' => [
                'source' => $data['source'] ?? 'manual',
            ],
        ]);

        $this->rebuildChunks($document, (int) ($data['chunk_size'] ?? 600));

        return redirect()
            ->route('chatbot.knowledge.index', $account)
            ->with('status', 'Dokumen knowledge diperbarui.');
    }

    public function destroy(ChatbotAccount $account, ChatbotKnowledgeDocument $document): RedirectResponse
    {
        $this->assertOwnership($account, $document);
        $document->delete();

        return back()->with('status', 'Dokumen knowledge dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:200000'],
            'chunk_size' => ['nullable', 'integer', 'min:300', 'max:1200'],
        ]);
    }

    private function assertOwnership(ChatbotAccount $account, ChatbotKnowledgeDocument $document): void
    {
        abort_unless((int) $document->chatbot_account_id === (int) $account->id, 404);
    }

    private function rebuildChunks(ChatbotKnowledgeDocument $document, int $chunkSize): void
    {
        $chunkSize = max(300, min($chunkSize, 1200));
        $text = trim((string) $document->content);
        $parts = $this->splitText($text, $chunkSize);

        $document->chunks()->delete();

        foreach ($parts as $index => $part) {
            $document->chunks()->create([
                'chatbot_account_id' => $document->chatbot_account_id,
                'chunk_index' => $index,
                'content' => $part,
                'content_length' => strlen($part),
            ]);
        }
    }

    private function splitText(string $text, int $chunkSize): array
    {
        if ($text === '') {
            return [];
        }

        $normalized = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;
        $paragraphs = preg_split("/\n{2,}/", $normalized) ?: [$normalized];

        $chunks = [];
        $buffer = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            $candidate = $buffer === '' ? $paragraph : $buffer . "\n\n" . $paragraph;
            if (strlen($candidate) <= $chunkSize) {
                $buffer = $candidate;
                continue;
            }

            if ($buffer !== '') {
                $chunks[] = $buffer;
                $buffer = '';
            }

            if (strlen($paragraph) <= $chunkSize) {
                $buffer = $paragraph;
                continue;
            }

            $sentences = preg_split('/(?<=[\.\!\?])\s+/', $paragraph) ?: [$paragraph];
            $sentenceBuffer = '';
            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if ($sentence === '') {
                    continue;
                }

                $sentenceCandidate = $sentenceBuffer === '' ? $sentence : $sentenceBuffer . ' ' . $sentence;
                if (strlen($sentenceCandidate) <= $chunkSize) {
                    $sentenceBuffer = $sentenceCandidate;
                } else {
                    if ($sentenceBuffer !== '') {
                        $chunks[] = $sentenceBuffer;
                    }
                    $sentenceBuffer = strlen($sentence) <= $chunkSize ? $sentence : substr($sentence, 0, $chunkSize);
                }
            }

            if ($sentenceBuffer !== '') {
                $chunks[] = $sentenceBuffer;
            }
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return array_values(array_filter(array_map('trim', $chunks), fn ($item) => $item !== ''));
    }
}

