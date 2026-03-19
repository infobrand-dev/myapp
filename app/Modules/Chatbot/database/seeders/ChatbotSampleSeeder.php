<?php

namespace App\Modules\Chatbot\Database\Seeders;

use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotKnowledgeChunk;
use App\Modules\Chatbot\Models\ChatbotKnowledgeDocument;
use App\Modules\Chatbot\Models\ChatbotMessage;
use App\Modules\Chatbot\Models\ChatbotSession;
use App\Support\SampleDataUserResolver;
use Illuminate\Database\Seeder;

class ChatbotSampleSeeder extends Seeder
{
    public function run(): void
    {
        $user = SampleDataUserResolver::resolve();

        if (!$user) {
            return;
        }

        $account = ChatbotAccount::query()->updateOrCreate(
            ['name' => 'Demo Support Bot'],
            [
                'provider' => 'openai',
                'model' => 'gpt-4.1-mini',
                'system_prompt' => 'Bantu jawab pertanyaan pelanggan dengan singkat dan sopan.',
                'focus_scope' => 'customer-support',
                'response_style' => 'friendly',
                'operation_mode' => 'assisted',
                'api_key' => 'demo-chatbot-key',
                'status' => 'active',
                'mirror_to_conversations' => true,
                'rag_enabled' => true,
                'rag_top_k' => 3,
                'metadata' => ['seeded' => true],
                'created_by' => $user->id,
            ]
        );

        $document = ChatbotKnowledgeDocument::query()->updateOrCreate(
            [
                'chatbot_account_id' => $account->id,
                'title' => 'FAQ Produk Demo',
            ],
            [
                'content' => 'Produk demo tersedia dalam ukuran 250gr dan dikirim dari gudang utama.',
                'metadata' => ['seeded' => true],
            ]
        );

        ChatbotKnowledgeChunk::query()->updateOrCreate(
            [
                'document_id' => $document->id,
                'chatbot_account_id' => $account->id,
                'chunk_index' => 0,
            ],
            [
                'content' => 'Produk demo tersedia dalam ukuran 250gr dan dikirim dari gudang utama.',
                'content_length' => 74,
                'metadata' => ['seeded' => true],
            ]
        );

        $session = ChatbotSession::query()->updateOrCreate(
            [
                'chatbot_account_id' => $account->id,
                'user_id' => $user->id,
                'title' => 'Sesi Demo Chatbot',
            ],
            [
                'metadata' => ['seeded' => true],
                'last_message_at' => now()->subMinutes(5),
            ]
        );

        ChatbotMessage::query()->firstOrCreate(
            [
                'session_id' => $session->id,
                'role' => 'user',
                'content' => 'Apakah produk demo ready stock?',
            ],
            [
                'provider_response' => ['seeded' => true],
                'prompt_tokens' => 12,
                'completion_tokens' => 0,
                'total_tokens' => 12,
            ]
        );

        ChatbotMessage::query()->firstOrCreate(
            [
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => 'Ya, stok tersedia dan pengiriman dilakukan dari gudang utama.',
            ],
            [
                'provider_response' => ['seeded' => true],
                'prompt_tokens' => 12,
                'completion_tokens' => 18,
                'total_tokens' => 30,
            ]
        );
    }
}
