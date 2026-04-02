<?php

namespace Tests\Feature\Chatbot;

use App\Modules\Chatbot\ChatbotServiceProvider;
use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotKnowledgeDocument;
use App\Modules\Chatbot\Services\RagContextBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotRagContextBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ChatbotServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Chatbot/database/migrations',
            '--force' => true,
        ]);
    }

    public function test_rag_prioritizes_exact_phrase_and_metadata_match(): void
    {
        $account = $this->makeAccount();

        $strongDocument = ChatbotKnowledgeDocument::query()->create([
            'tenant_id' => 1,
            'chatbot_account_id' => $account->id,
            'title' => 'Paket Growth untuk outlet',
            'content' => 'Paket Growth cocok untuk outlet kecil yang butuh WhatsApp API dan AI untuk tim CS.',
            'metadata' => [
                'status' => 'active',
                'priority' => 10,
                'category' => 'pricing',
                'language' => 'id',
            ],
            'updated_at' => now(),
        ]);
        $strongDocument->chunks()->create([
            'chatbot_account_id' => $account->id,
            'chunk_index' => 0,
            'content' => 'Paket Growth cocok untuk outlet kecil yang butuh WhatsApp API dan AI untuk tim CS.',
            'content_length' => 82,
        ]);

        $weakDocument = ChatbotKnowledgeDocument::query()->create([
            'tenant_id' => 1,
            'chatbot_account_id' => $account->id,
            'title' => 'Panduan retur barang',
            'content' => 'Prosedur retur barang dan refund customer untuk pesanan marketplace.',
            'metadata' => [
                'status' => 'active',
                'priority' => 1,
                'category' => 'returns',
                'language' => 'id',
            ],
            'updated_at' => now()->subDays(200),
        ]);
        $weakDocument->chunks()->create([
            'chatbot_account_id' => $account->id,
            'chunk_index' => 0,
            'content' => 'Prosedur retur barang dan refund customer untuk pesanan marketplace.',
            'content_length' => 69,
        ]);

        $results = app(RagContextBuilder::class)->retrieve($account, 'paket growth untuk outlet');

        $this->assertNotEmpty($results);
        $this->assertSame($strongDocument->id, $results[0]['document_id']);
        $this->assertGreaterThan($results[1]['score'] ?? 0, $results[0]['score']);
    }

    public function test_rag_understands_basic_synonym_queries(): void
    {
        $account = $this->makeAccount();

        $document = ChatbotKnowledgeDocument::query()->create([
            'tenant_id' => 1,
            'chatbot_account_id' => $account->id,
            'title' => 'Info harga paket Growth',
            'content' => 'Harga paket Growth adalah pilihan yang cocok untuk outlet dengan admin dan CS kecil.',
            'metadata' => [
                'status' => 'active',
                'priority' => 8,
                'category' => 'pricing',
                'language' => 'id',
            ],
        ]);
        $document->chunks()->create([
            'chatbot_account_id' => $account->id,
            'chunk_index' => 0,
            'content' => 'Harga paket Growth adalah pilihan yang cocok untuk outlet dengan admin dan CS kecil.',
            'content_length' => 83,
        ]);

        $results = app(RagContextBuilder::class)->retrieve($account, 'berapa biaya plan growth');

        $this->assertNotEmpty($results);
        $this->assertSame($document->id, $results[0]['document_id']);
        $this->assertGreaterThan(0, $results[0]['score']);
    }

    public function test_rag_penalizes_inactive_documents(): void
    {
        $account = $this->makeAccount();

        $inactiveDocument = ChatbotKnowledgeDocument::query()->create([
            'tenant_id' => 1,
            'chatbot_account_id' => $account->id,
            'title' => 'Promo lama outlet',
            'content' => 'Promo diskon outlet lama dengan voucher khusus pelanggan lama.',
            'metadata' => [
                'status' => 'draft',
                'priority' => 10,
                'category' => 'promo',
                'language' => 'id',
            ],
        ]);
        $inactiveDocument->chunks()->create([
            'chatbot_account_id' => $account->id,
            'chunk_index' => 0,
            'content' => 'Promo diskon outlet lama dengan voucher khusus pelanggan lama.',
            'content_length' => 64,
        ]);

        $activeDocument = ChatbotKnowledgeDocument::query()->create([
            'tenant_id' => 1,
            'chatbot_account_id' => $account->id,
            'title' => 'Promo aktif outlet',
            'content' => 'Promo diskon outlet aktif berlaku bulan ini untuk pelanggan baru.',
            'metadata' => [
                'status' => 'active',
                'priority' => 5,
                'category' => 'promo',
                'language' => 'id',
            ],
        ]);
        $activeDocument->chunks()->create([
            'chatbot_account_id' => $account->id,
            'chunk_index' => 0,
            'content' => 'Promo diskon outlet aktif berlaku bulan ini untuk pelanggan baru.',
            'content_length' => 64,
        ]);

        $results = app(RagContextBuilder::class)->retrieve($account, 'promo diskon outlet');

        $this->assertNotEmpty($results);
        $this->assertSame($activeDocument->id, $results[0]['document_id']);
        $this->assertNotContains($inactiveDocument->id, array_column($results, 'document_id'));
    }

    private function makeAccount(): ChatbotAccount
    {
        return ChatbotAccount::query()->create([
            'tenant_id' => 1,
            'name' => 'Rag Bot',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'api_key' => 'sk-test',
            'status' => 'active',
            'rag_enabled' => true,
            'rag_top_k' => 3,
        ]);
    }
}
