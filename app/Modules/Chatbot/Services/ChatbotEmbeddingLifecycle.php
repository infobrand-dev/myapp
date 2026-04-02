<?php

namespace App\Modules\Chatbot\Services;

class ChatbotEmbeddingLifecycle
{
    /**
     * @return array<string, mixed>
     */
    public function pendingAttributesForContent(string $content): array
    {
        return [
            'embedding_status' => 'pending',
            'embedding_provider' => null,
            'embedding_model' => null,
            'embedding_source_hash' => $this->sourceHash($content),
            'embedding_generated_at' => null,
            'embedding_dimensions' => null,
            'embedding_error' => null,
            'embedding_metadata' => null,
        ];
    }

    public function sourceHash(string $content): string
    {
        return hash('sha256', trim($content));
    }
}
