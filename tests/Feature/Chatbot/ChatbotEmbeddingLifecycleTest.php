<?php

namespace Tests\Feature\Chatbot;

use App\Modules\Chatbot\ChatbotServiceProvider;
use App\Modules\Chatbot\Services\ChatbotEmbeddingLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatbotEmbeddingLifecycleTest extends TestCase
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

    public function test_chatbot_chunks_are_embedding_ready(): void
    {
        $this->assertTrue(Schema::hasColumn('chatbot_knowledge_chunks', 'embedding_status'));
        $this->assertTrue(Schema::hasColumn('chatbot_knowledge_chunks', 'embedding_source_hash'));
        $this->assertTrue(Schema::hasColumn('chatbot_knowledge_chunks', 'embedding_metadata'));

        $payload = app(ChatbotEmbeddingLifecycle::class)->pendingAttributesForContent('Paket Growth cocok untuk outlet kecil.');

        $this->assertSame('pending', $payload['embedding_status']);
        $this->assertSame(hash('sha256', 'Paket Growth cocok untuk outlet kecil.'), $payload['embedding_source_hash']);
        $this->assertNull($payload['embedding_generated_at']);
        $this->assertNull($payload['embedding_model']);
    }
}
