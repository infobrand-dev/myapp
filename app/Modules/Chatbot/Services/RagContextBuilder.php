<?php

namespace App\Modules\Chatbot\Services;

use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotKnowledgeChunk;
use Illuminate\Support\Str;

class RagContextBuilder
{
    public function retrieve(ChatbotAccount $account, string $query): array
    {
        $terms = $this->extractTerms($query);
        if (empty($terms)) {
            return [];
        }

        $normalizedQuery = Str::lower(trim($query));

        $candidateQuery = ChatbotKnowledgeChunk::query()
            ->with('document:id,title')
            ->where('chatbot_account_id', $account->id);

        $candidateQuery->whereHas('document', function ($query) {
            $query->where(function ($documentQuery) {
                $documentQuery->whereNull('metadata->status')
                    ->orWhere('metadata->status', 'active');
            });
        });

        $candidateQuery->where(function ($q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere('content', 'like', '%' . $term . '%');
            }
        });

        $candidates = $candidateQuery
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        $scored = $candidates->map(function (ChatbotKnowledgeChunk $chunk) use ($terms, $normalizedQuery) {
            $content = Str::lower((string) $chunk->content);
            $score = 0;
            foreach ($terms as $term) {
                if (Str::contains($content, $term)) {
                    $score += 3;
                }
            }

            $title = Str::lower((string) data_get($chunk, 'document.title'));
            foreach ($terms as $term) {
                if (Str::contains($title, $term)) {
                    $score += 2;
                }
            }

            if ($normalizedQuery !== '' && Str::contains($content, $normalizedQuery)) {
                $score += 5;
            }

            $metadata = is_array(data_get($chunk, 'document.metadata')) ? data_get($chunk, 'document.metadata') : [];
            $priority = max(1, min((int) ($metadata['priority'] ?? 1), 10));
            $score += min($priority, 5);

            $updatedAt = data_get($chunk, 'document.updated_at');
            if ($updatedAt && method_exists($updatedAt, 'greaterThan')) {
                if ($updatedAt->greaterThan(now()->subDays(7))) {
                    $score += 2;
                } elseif ($updatedAt->greaterThan(now()->subDays(30))) {
                    $score += 1;
                }
            }

            return [
                'chunk' => $chunk,
                'score' => $score,
            ];
        })->filter(fn ($item) => ($item['score'] ?? 0) > 0)
            ->sortByDesc('score')
            ->values();

        if ($scored->isEmpty()) {
            return [];
        }

        $topK = max(1, min((int) ($account->rag_top_k ?: 3), 8));
        $selected = $scored->take($topK);

        return $selected->map(function ($item) {
            /** @var ChatbotKnowledgeChunk $chunk */
            $chunk = $item['chunk'];
            return [
                'chunk_id' => $chunk->id,
                'document_id' => $chunk->document_id,
                'title' => (string) data_get($chunk, 'document.title', 'Dokumen'),
                'content' => Str::limit((string) $chunk->content, 700, '...'),
                'document_metadata' => is_array(data_get($chunk, 'document.metadata')) ? data_get($chunk, 'document.metadata') : [],
                'score' => (int) ($item['score'] ?? 0),
            ];
        })->all();
    }

    private function extractTerms(string $text): array
    {
        $clean = Str::lower($text);
        $clean = preg_replace('/[^a-z0-9\s]/', ' ', $clean) ?? $clean;
        $parts = preg_split('/\s+/', $clean) ?: [];

        $terms = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || strlen($part) < 3) {
                continue;
            }
            $terms[] = $part;
        }

        return array_values(array_slice(array_unique($terms), 0, 12));
    }
}
