<?php

namespace App\Modules\AiModule\Providers;

use App\Exceptions\AppException;
use App\Models\ModelMessage;
use App\Modules\AiModule\Engines\PromptEngine;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Auth;
use Psr\Http\Message\ResponseInterface;

class DeepSeek
{
    private static string $baseUrl = "https://openrouter.ai/api/v1/chat/completions";
    private static string $apiKey = "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";
    private static Client $client;

    /**
     * Initialize the Gemini provider.
     *
     * @throws AppException If the API key is missing.
     */
    public static function initialize(): void
    {
        if (empty(self::$apiKey)) {
            throw new AppException("Missing API key");
        }
        self::$client = new Client();
    }

    /**
     * Generate system messages for the API request.
     *
     * @param string $content The user's input content.
     * @return array The system messages.
     */
    private static function systemMessages(string $content): array
    {
        return [['role' => 'system', 'content' => PromptEngine::getPromptPrefix($content)]];
    }

    /**
     * Retrieve previous messages for a conversation.
     *
     * @param int $conversationId The ID of the conversation.
     * @return array The formatted previous messages.
     */
    public static function getPreviousMessages(int $conversationId): array
    {
        $messages = ModelMessage::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();

        $formattedMessages = $messages->map(function ($message) {
            return [
                'role' => $message->is_model ? 'assistant' : 'user',
                'content' => $message->message,
            ];
        });

        return $formattedMessages->toArray();
    }

    /**
     * Query the Gemini API with the user's input and conversation context.
     *
     * @param string $text The user's input text.
     * @param int|null $conversationId The ID of the conversation.
     * @param bool|null $use_context Whether to use conversation context.
     * @return mixed The API response.
     * @throws AppException If authentication fails or the API request fails.
     */
    public static function query(string $text, ?int $conversationId, ?bool $use_context = true): mixed
    {
        if (!$userId = Auth::id()) {
            throw new AppException("Authentication required");
        }

        // Preload system messages and previous conversation context
        $preloads = $use_context ? array_merge(
            self::systemMessages($text),
            self::getPreviousMessages($conversationId)
        ) : [];

        // Build the payload
        $payload = [
            'model' => 'deepseek/deepseek-r1-zero:free',
            'messages' => array_merge(
                $preloads,
                [['role' => 'user', 'content' => $text]]
            ),
        ];

        // Debug payload
        \Log::info('API Payload:', $payload);

        return self::sendRequest(self::buildUri(), $payload);
    }

    /**
     * Build the API request URI.
     *
     * @return string The complete API URI.
     */
    private static function buildUri(): string
    {
        return self::$baseUrl . "?key=" . self::$apiKey;
    }

    /**
     * Send the API request.
     *
     * @param string $uri The API endpoint.
     * @param array $payload The request payload.
     * @return mixed The API response.
     * @throws AppException If the API request fails.
     */
    private static function sendRequest(string $uri, array $payload): mixed
    {
        try {
            $response = self::$client->post($uri, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . self::$apiKey,
                ],
                'json' => $payload,
            ]);

            return self::processResponse($response);
        } catch (RequestException $e) {
            throw new AppException("API error: " . ($e->getResponse()?->getStatusCode() ?? "Connection failed"));
        }
    }

    /**
     * Process the API response.
     *
     * @param ResponseInterface $response The API response.
     * @return string The processed response content.
     * @throws AppException If the API request fails.
     */
    private static function processResponse(ResponseInterface $response): string
    {
        if ($response->getStatusCode() !== 200) {
            throw new AppException("API request failed");
        }

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['choices'][0]['message']['content'] ?? '';
    }
}