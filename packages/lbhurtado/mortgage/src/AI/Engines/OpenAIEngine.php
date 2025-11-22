<?php

namespace LBHurtado\Mortgage\AI\Engines;

use LBHurtado\Mortgage\AI\Contracts\AIEngineInterface;
use Illuminate\Support\Facades\Http;

class OpenAIEngine implements AIEngineInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? config('mortgage.ai.openai.api_key');
        $this->model = $model ?? config('mortgage.ai.openai.model', 'gpt-4o-mini');
    }

    public function chat(array $messages, array $options = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/chat/completions", array_merge([
            'model' => $this->model,
            'messages' => $messages,
        ], $options));

        if ($response->failed()) {
            throw new \Exception("OpenAI API request failed: " . $response->body());
        }

        $data = $response->json();

        return [
            'content' => $data['choices'][0]['message']['content'] ?? null,
            'role' => $data['choices'][0]['message']['role'] ?? 'assistant',
            'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'provider' => 'openai',
            'model' => $data['model'] ?? $this->model,
        ];
    }

    public function chatWithTools(array $messages, array $tools, array $options = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/chat/completions", array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'tools' => $tools,
            'tool_choice' => 'auto',
        ], $options));

        if ($response->failed()) {
            throw new \Exception("OpenAI API request failed: " . $response->body());
        }

        $data = $response->json();
        $message = $data['choices'][0]['message'] ?? [];

        return [
            'content' => $message['content'] ?? null,
            'role' => $message['role'] ?? 'assistant',
            'tool_calls' => $message['tool_calls'] ?? null,
            'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'provider' => 'openai',
            'model' => $data['model'] ?? $this->model,
        ];
    }

    public function stream(array $messages, array $options = []): \Generator
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(60)->post("{$this->baseUrl}/chat/completions", array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'stream' => true,
        ], $options));

        if ($response->failed()) {
            throw new \Exception("OpenAI API request failed: " . $response->body());
        }

        // Note: Actual streaming implementation requires SSE handling
        // This is a simplified version
        yield [
            'content' => $response->json()['choices'][0]['message']['content'] ?? '',
            'done' => true,
        ];
    }

    public function getProvider(): string
    {
        return 'openai';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function supportsFunctionCalling(): bool
    {
        return true;
    }

    public function supportsStreaming(): bool
    {
        return true;
    }
}
