<?php

namespace App\Modules\Chatbot\Services;

use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotKnowledgeChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RagContextBuilder
{
    private const STOP_WORDS = [
        'yang', 'untuk', 'dengan', 'atau', 'dan', 'dari', 'agar', 'supaya', 'bisa', 'boleh',
        'adalah', 'apa', 'apakah', 'bagaimana', 'gimana', 'kenapa', 'mengapa', 'tolong',
        'saya', 'kami', 'kita', 'anda', 'mereka', 'ini', 'itu', 'nya', 'nih', 'dong',
        'the', 'and', 'for', 'with', 'from', 'that', 'this', 'can', 'could', 'would',
        'about', 'please', 'help',
    ];

    private const SYNONYM_GROUPS = [
        ['harga', 'biaya', 'tarif', 'price', 'pricing', 'cost'],
        ['paket', 'plan', 'langganan', 'subscription'],
        ['outlet', 'cabang', 'branch', 'store', 'toko'],
        ['admin', 'agent', 'cs', 'customer service', 'support', 'manusia', 'human'],
        ['retur', 'refund', 'pengembalian'],
        ['kirim', 'pengiriman', 'delivery', 'shipment'],
        ['jam', 'jadwal', 'schedule', 'operasional', 'buka'],
        ['bayar', 'pembayaran', 'payment', 'invoice', 'tagihan'],
        ['produk', 'barang', 'item', 'catalog', 'katalog'],
        ['promo', 'diskon', 'discount', 'voucher'],
    ];

    public function retrieve(ChatbotAccount $account, string $query): array
    {
        $normalizedQuery = $this->normalizeText($query);
        if ($normalizedQuery === '') {
            return [];
        }

        $queryTokens = $this->expandTokens($this->extractTerms($normalizedQuery));
        $queryPhrases = $this->extractPhrases($normalizedQuery, $queryTokens);
        $queryLanguage = $this->detectLanguage($normalizedQuery, $queryTokens);

        if ($queryTokens === [] && $queryPhrases === []) {
            return [];
        }

        $candidateQuery = ChatbotKnowledgeChunk::query()
            ->with('document:id,title,metadata,updated_at')
            ->where('chatbot_account_id', $account->id);

        $candidateQuery->whereHas('document', function ($query) {
            $query->where(function ($documentQuery) {
                $documentQuery->whereNull('metadata->status')
                    ->orWhere('metadata->status', 'active');
            });
        });

        $candidateQuery->where(function ($q) use ($queryTokens, $queryPhrases) {
            foreach (array_slice($queryTokens, 0, 10) as $term) {
                $q->orWhere('content', 'like', '%' . $term . '%');
                $q->orWhereHas('document', fn ($documentQuery) => $documentQuery->where('title', 'like', '%' . $term . '%'));
            }

            foreach (array_slice($queryPhrases, 0, 4) as $phrase) {
                $q->orWhere('content', 'like', '%' . $phrase . '%');
                $q->orWhereHas('document', fn ($documentQuery) => $documentQuery->where('title', 'like', '%' . $phrase . '%'));
            }
        });

        $candidates = $candidateQuery
            ->orderByDesc('id')
            ->limit(120)
            ->get();

        $scored = $candidates
            ->map(fn (ChatbotKnowledgeChunk $chunk) => $this->scoreChunk(
                $chunk,
                $normalizedQuery,
                $queryTokens,
                $queryPhrases,
                $queryLanguage
            ))
            ->filter(fn (array $item) => ($item['score'] ?? 0) > 0)
            ->sortByDesc('score')
            ->values();

        if ($scored->isEmpty()) {
            return [];
        }

        $topK = max(1, min((int) ($account->rag_top_k ?: 3), 8));

        return $scored
            ->take($topK)
            ->map(function (array $item) {
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
            })
            ->all();
    }

    /**
     * @param array<int, string> $queryTokens
     * @param array<int, string> $queryPhrases
     * @return array{chunk: ChatbotKnowledgeChunk, score: int}
     */
    private function scoreChunk(
        ChatbotKnowledgeChunk $chunk,
        string $normalizedQuery,
        array $queryTokens,
        array $queryPhrases,
        string $queryLanguage
    ): array {
        $content = $this->normalizeText((string) $chunk->content);
        $title = $this->normalizeText((string) data_get($chunk, 'document.title'));
        $metadata = is_array(data_get($chunk, 'document.metadata')) ? data_get($chunk, 'document.metadata') : [];

        $category = $this->normalizeText((string) ($metadata['category'] ?? ''));
        $source = $this->normalizeText((string) ($metadata['source'] ?? ''));
        $docLanguage = strtolower((string) ($metadata['language'] ?? ''));
        $priority = max(1, min((int) ($metadata['priority'] ?? 5), 10));
        $status = strtolower((string) ($metadata['status'] ?? 'active'));

        $contentTokens = $this->extractTerms($content);
        $titleTokens = $this->extractTerms($title);
        $allDocTokens = array_values(array_unique(array_merge($contentTokens, $titleTokens)));

        $score = 0;

        if ($normalizedQuery !== '' && $content !== '' && Str::contains($content, $normalizedQuery)) {
            $score += 18;
        }

        if ($normalizedQuery !== '' && $title !== '' && Str::contains($title, $normalizedQuery)) {
            $score += 14;
        }

        foreach ($queryPhrases as $phrase) {
            if ($phrase === '') {
                continue;
            }

            if (Str::contains($title, $phrase)) {
                $score += 12;
            }

            if (Str::contains($content, $phrase)) {
                $score += 9;
            }
        }

        foreach ($queryTokens as $token) {
            if ($token === '') {
                continue;
            }

            if (Str::contains($title, $token)) {
                $score += 7;
            }

            if (Str::contains($content, $token)) {
                $score += 4;
            }

            if ($category !== '' && Str::contains($category, $token)) {
                $score += 6;
            }

            if ($source !== '' && Str::contains($source, $token)) {
                $score += 2;
            }
        }

        $overlapCount = count(array_intersect($queryTokens, $allDocTokens));
        $score += $overlapCount * 4;

        $coverage = count($queryTokens) > 0 ? $overlapCount / max(count($queryTokens), 1) : 0;
        if ($coverage >= 0.8) {
            $score += 10;
        } elseif ($coverage >= 0.5) {
            $score += 6;
        } elseif ($coverage >= 0.3) {
            $score += 3;
        }

        if ($queryLanguage !== '' && $docLanguage !== '') {
            if ($queryLanguage === $docLanguage) {
                $score += 4;
            } else {
                $score -= 2;
            }
        }

        if ($status !== 'active' && $status !== '') {
            $score -= 12;
        }

        $score += min($priority, 6);

        $updatedAt = data_get($chunk, 'document.updated_at');
        if ($updatedAt && method_exists($updatedAt, 'greaterThan')) {
            if ($updatedAt->greaterThan(now()->subDays(7))) {
                $score += 3;
            } elseif ($updatedAt->greaterThan(now()->subDays(30))) {
                $score += 1;
            } elseif ($updatedAt->lessThan(now()->subDays(180))) {
                $score -= 2;
            }
        }

        return [
            'chunk' => $chunk,
            'score' => max(0, (int) round($score)),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractTerms(string $text): array
    {
        $clean = $this->normalizeText($text);
        $parts = preg_split('/\s+/', $clean) ?: [];

        $terms = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || strlen($part) < 2) {
                continue;
            }

            if (in_array($part, self::STOP_WORDS, true)) {
                continue;
            }

            $terms[] = $part;
        }

        return array_values(array_slice(array_unique($terms), 0, 18));
    }

    /**
     * @param array<int, string> $tokens
     * @return array<int, string>
     */
    private function expandTokens(array $tokens): array
    {
        $expanded = $tokens;

        foreach ($tokens as $token) {
            foreach (self::SYNONYM_GROUPS as $group) {
                if (in_array($token, $group, true)) {
                    $expanded = array_merge($expanded, $group);
                }
            }
        }

        return array_values(array_slice(array_unique($expanded), 0, 24));
    }

    /**
     * @param array<int, string> $tokens
     * @return array<int, string>
     */
    private function extractPhrases(string $normalizedQuery, array $tokens): array
    {
        $phrases = [];

        if (str_word_count($normalizedQuery) >= 2) {
            $phrases[] = $normalizedQuery;
        }

        $tokenCount = count($tokens);
        for ($i = 0; $i < $tokenCount - 1; $i++) {
            $phrases[] = $tokens[$i] . ' ' . $tokens[$i + 1];
        }

        return array_values(array_slice(array_unique(array_filter($phrases)), 0, 8));
    }

    private function normalizeText(string $text): string
    {
        $text = Str::lower(trim($text));
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @param array<int, string> $tokens
     */
    private function detectLanguage(string $normalizedQuery, array $tokens): string
    {
        $indicatorsId = ['harga', 'paket', 'cabang', 'retur', 'jadwal', 'bayar', 'pengiriman'];
        $indicatorsEn = ['price', 'plan', 'branch', 'refund', 'schedule', 'payment', 'delivery'];

        $idScore = count(array_intersect($tokens, $indicatorsId));
        $enScore = count(array_intersect($tokens, $indicatorsEn));

        if (Str::contains($normalizedQuery, ' yang ') || Str::contains($normalizedQuery, ' untuk ')) {
            $idScore++;
        }

        if (Str::contains($normalizedQuery, ' for ') || Str::contains($normalizedQuery, ' with ')) {
            $enScore++;
        }

        return $idScore >= $enScore ? 'id' : 'en';
    }
}
