<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiProviderClient
{
    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     * @return array{reply:string, usage:array<string, int>|null, raw:array|null}
     */
    public function chat(
        string $provider,
        string $apiKey,
        string $model,
        array $messages,
        float $temperature = 0.5,
        int $maxTokens = 400,
        int $timeoutSeconds = 30,
    ): array {
        return match (strtolower($provider)) {
            'anthropic' => $this->chatAnthropic($apiKey, $model, $messages, $temperature, $maxTokens, $timeoutSeconds),
            'groq' => $this->chatGroq($apiKey, $model, $messages, $temperature, $maxTokens, $timeoutSeconds),
            default => $this->chatOpenAi($apiKey, $model, $messages, $temperature, $maxTokens, $timeoutSeconds),
        };
    }

    public function verifyApiKey(string $provider, string $apiKey): bool
    {
        $provider = strtolower($provider);

        return match ($provider) {
            'anthropic' => $this->verifyAnthropic($apiKey),
            'groq' => $this->verifyGroq($apiKey),
            default => $this->verifyOpenAi($apiKey),
        };
    }

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     * @return array{reply:string, usage:array<string, int>|null, raw:array|null}
     */
    private function chatOpenAi(string $apiKey, string $model, array $messages, float $temperature, int $maxTokens, int $timeoutSeconds): array
    {
        $response = Http::withToken($apiKey)
            ->timeout($timeoutSeconds)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

        return $this->normalizeOpenAiCompatibleResponse($response);
    }

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     * @return array{reply:string, usage:array<string, int>|null, raw:array|null}
     */
    private function chatGroq(string $apiKey, string $model, array $messages, float $temperature, int $maxTokens, int $timeoutSeconds): array
    {
        $response = Http::withToken($apiKey)
            ->timeout($timeoutSeconds)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

        return $this->normalizeOpenAiCompatibleResponse($response);
    }

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     * @return array{reply:string, usage:array<string, int>|null, raw:array|null}
     */
    private function chatAnthropic(string $apiKey, string $model, array $messages, float $temperature, int $maxTokens, int $timeoutSeconds): array
    {
        $system = collect($messages)
            ->filter(fn (array $message) => ($message['role'] ?? '') === 'system')
            ->pluck('content')
            ->filter(fn ($content) => is_string($content) && trim($content) !== '')
            ->implode("\n\n");

        $anthropicMessages = collect($messages)
            ->filter(fn (array $message) => in_array($message['role'] ?? '', ['user', 'assistant'], true))
            ->map(fn (array $message) => [
                'role' => $message['role'],
                'content' => $message['content'],
            ])
            ->values()
            ->all();

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => $anthropicMessages,
        ];

        if ($system !== '') {
            $payload['system'] = $system;
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout($timeoutSeconds)
            ->post('https://api.anthropic.com/v1/messages', $payload);

        $raw = $response->json();
        if (!$response->successful()) {
            throw new RuntimeException((string) (Arr::get($raw, 'error.message') ?: $response->body() ?: 'Anthropic request failed'));
        }

        $reply = collect((array) Arr::get($raw, 'content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n\n");

        $promptTokens = (int) Arr::get($raw, 'usage.input_tokens', 0);
        $completionTokens = (int) Arr::get($raw, 'usage.output_tokens', 0);

        return [
            'reply' => trim($reply),
            'usage' => [
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $promptTokens + $completionTokens,
            ],
            'raw' => is_array($raw) ? $raw : null,
        ];
    }

    private function verifyOpenAi(string $apiKey): bool
    {
        return Http::withToken($apiKey)
            ->timeout(8)
            ->get('https://api.openai.com/v1/models')
            ->successful();
    }

    private function verifyGroq(string $apiKey): bool
    {
        return Http::withToken($apiKey)
            ->timeout(8)
            ->get('https://api.groq.com/openai/v1/models')
            ->successful();
    }

    private function verifyAnthropic(string $apiKey): bool
    {
        return Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(8)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-haiku-4-5-20251001',
                'max_tokens' => 1,
                'messages' => [['role' => 'user', 'content' => 'Hi']],
            ])->status() !== 401;
    }

    /**
     * @return array{reply:string, usage:array<string, int>|null, raw:array|null}
     */
    private function normalizeOpenAiCompatibleResponse(Response $response): array
    {
        $raw = $response->json();

        if (!$response->successful()) {
            throw new RuntimeException((string) (Arr::get($raw, 'error.message') ?: $response->body() ?: 'AI request failed'));
        }

        $usage = Arr::get($raw, 'usage');

        return [
            'reply' => trim((string) (Arr::get($raw, 'choices.0.message.content') ?? '')),
            'usage' => is_array($usage) ? [
                'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
            ] : null,
            'raw' => is_array($raw) ? $raw : null,
        ];
    }
}
