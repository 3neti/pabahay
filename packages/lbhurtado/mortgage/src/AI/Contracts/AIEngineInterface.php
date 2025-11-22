<?php

namespace LBHurtado\Mortgage\AI\Contracts;

interface AIEngineInterface
{
    /**
     * Send a chat completion request
     *
     * @param array $messages Array of message objects with role and content
     * @param array $options Optional parameters (temperature, max_tokens, etc.)
     * @return array Response with message content and metadata
     */
    public function chat(array $messages, array $options = []): array;

    /**
     * Send a chat completion request with function/tool calling
     *
     * @param array $messages Array of message objects
     * @param array $tools Array of tool definitions
     * @param array $options Optional parameters
     * @return array Response with message content, tool calls, and metadata
     */
    public function chatWithTools(array $messages, array $tools, array $options = []): array;

    /**
     * Stream a chat completion request
     *
     * @param array $messages Array of message objects
     * @param array $options Optional parameters
     * @return \Generator Yields chunks of response
     */
    public function stream(array $messages, array $options = []): \Generator;

    /**
     * Get provider name
     *
     * @return string Provider identifier (openai, claude, gemini)
     */
    public function getProvider(): string;

    /**
     * Get model name being used
     *
     * @return string Model identifier
     */
    public function getModel(): string;

    /**
     * Check if provider supports function/tool calling
     *
     * @return bool
     */
    public function supportsFunctionCalling(): bool;

    /**
     * Check if provider supports streaming
     *
     * @return bool
     */
    public function supportsStreaming(): bool;
}
