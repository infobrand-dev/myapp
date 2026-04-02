<?php

namespace App\Modules\Chatbot\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chatbot\Http\Requests\StoreChatbotKnowledgeRequest;
use App\Modules\Chatbot\Http\Requests\UpdateChatbotKnowledgeRequest;
use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotDecisionLog;
use App\Modules\Chatbot\Models\ChatbotKnowledgeDocument;
use App\Modules\Chatbot\Services\ChatbotEmbeddingLifecycle;
use App\Support\PlanLimit;
use App\Support\TenantPlanManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ChatbotKnowledgeController extends Controller
{
    public function __construct(private readonly ChatbotEmbeddingLifecycle $embeddingLifecycle)
    {
    }

    public function index(ChatbotAccount $account): View
    {
        $documents = ChatbotKnowledgeDocument::query()
            ->where('chatbot_account_id', $account->id)
            ->withCount('chunks')
            ->orderByDesc('updated_at')
            ->paginate(20);

        $topKnowledgeDocuments = collect();
        if (Schema::hasTable('chatbot_decision_logs')) {
            $topDocumentIds = ChatbotDecisionLog::query()
                ->where('tenant_id', $account->tenant_id)
                ->where('chatbot_account_id', $account->id)
                ->orderByDesc('id')
                ->limit(200)
                ->get()
                ->flatMap(function (ChatbotDecisionLog $log) {
                    $metadata = is_array($log->metadata) ? $log->metadata : [];
                    return array_values(array_filter((array) ($metadata['knowledge_document_ids'] ?? [])));
                })
                ->countBy()
                ->sortDesc()
                ->take(5);

            $topKnowledgeDocuments = ChatbotKnowledgeDocument::query()
                ->whereIn('id', $topDocumentIds->keys()->all())
                ->get()
                ->sortByDesc(fn (ChatbotKnowledgeDocument $document) => (int) ($topDocumentIds[$document->id] ?? 0))
                ->values();
        }

        return view('chatbot::knowledge.index', compact('account', 'documents', 'topKnowledgeDocuments'));
    }

    public function create(ChatbotAccount $account): View
    {
        $document = new ChatbotKnowledgeDocument();
        return view('chatbot::knowledge.form', compact('account', 'document'));
    }

    public function store(StoreChatbotKnowledgeRequest $request, ChatbotAccount $account): RedirectResponse
    {
        app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS);

        $data = $this->validated($request);
        $document = ChatbotKnowledgeDocument::query()->create([
            'tenant_id' => $account->tenant_id,
            'chatbot_account_id' => $account->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'metadata' => [
                'source' => $data['source'] ?? 'manual',
                'category' => $data['category'] ?? null,
                'language' => $data['language'] ?? 'id',
                'status' => $data['status'] ?? 'active',
                'priority' => (int) ($data['priority'] ?? 5),
            ],
        ]);

        $this->rebuildChunks($document, (int) ($data['chunk_size'] ?? 600));

        return redirect()
            ->route('chatbot.knowledge.index', $account)
            ->with('status', 'Dokumen ditambahkan.');
    }

    public function edit(ChatbotAccount $account, ChatbotKnowledgeDocument $document): View
    {
        $this->assertOwnership($account, $document);
        return view('chatbot::knowledge.form', compact('account', 'document'));
    }

    public function update(UpdateChatbotKnowledgeRequest $request, ChatbotAccount $account, ChatbotKnowledgeDocument $document): RedirectResponse
    {
        $this->assertOwnership($account, $document);
        $data = $this->validated($request);

        $document->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'metadata' => [
                'source' => $data['source'] ?? 'manual',
                'category' => $data['category'] ?? null,
                'language' => $data['language'] ?? 'id',
                'status' => $data['status'] ?? 'active',
                'priority' => (int) ($data['priority'] ?? 5),
            ],
        ]);

        $this->rebuildChunks($document, (int) ($data['chunk_size'] ?? 600));

        return redirect()
            ->route('chatbot.knowledge.index', $account)
            ->with('status', 'Dokumen diperbarui.');
    }

    public function destroy(ChatbotAccount $account, ChatbotKnowledgeDocument $document): RedirectResponse
    {
        $this->assertOwnership($account, $document);
        $document->delete();

        return back()->with('status', 'Dokumen dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validated();
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
                ...$this->embeddingLifecycle->pendingAttributesForContent($part),
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
