<?php

namespace App\Modules\AiModule\Engines;

use App\Exceptions\AppException;
use App\Models\ModelMessage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\ResponseInterface;

class ProviderEngine
{
    private const USER_THROTTLE_LIMIT = 5;

    // Define provider configurations with rate limiting
    private const USER_THROTTLE_DURATION = 7;

    // User throttling configuration
    private const API_KEY = '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';
    public static array $providers = [
        'OpenChat' => [
            'baseUrl' => 'https://openrouter.ai/api/v1/chat/completions',
            'apiKey' => self::API_KEY,
            'model' => 'OpenChat/OpenChat-7b:free',
            'rateLimit' => 5 // requests per second
        ],
        'DeepSeek' => [
            'baseUrl' => 'https://openrouter.ai/api/v1/chat/completions',
            'apiKey' => self::API_KEY,
            'model' => 'deepseek/deepseek-r1-zero:free',
            'rateLimit' => 5
        ],
        'Gemini' => [
            'baseUrl' => 'https://openrouter.ai/api/v1/chat/completions',
            'apiKey' => self::API_KEY,
            'model' => 'google/gemma-3-27b-it:free',
            'rateLimit' => 5
        ],
        'MetaAi' => [
            'baseUrl' => 'https://openrouter.ai/api/v1/chat/completions',
            'apiKey' => self::API_KEY,
            'model' => 'meta-llama/llama-3.2-11b-vision-instruct:free',
            'rateLimit' => 5
        ],
    ]; // Seconds
    private static Client $client;
    private static string $selectedProvider;
    private static array $rateLimits = [];

    /**
     * Query the API with improved error handling and retry logic.
     */
    public static function query(
        string $text,
        ?int   $conversationId,
        ?bool  $use_context = true,
        string $provider
    ): string
    {
        var_dump($provider);

        if (!$userId = Auth::id()) {
            throw new AppException("Authentication required");
        }

        // Check user throttle
        if (self::isUserThrottled($userId)) {
            throw new AppException("Too many requests. Please wait " . self::USER_THROTTLE_DURATION . " seconds before trying again.");
        }

        try {
            self::initialize($provider);

            // Build the payload with context if needed
            $payload = [
                'model' => self::$providers[$provider]['model'],
                'messages' => array_merge(
                    $use_context ? self::systemMessages($text) : [],
                    $use_context && $conversationId ? self::getPreviousMessages($conversationId) : [],
                    [['role' => 'user', 'content' => PromptEngine::vetInput($text)]]
                ),
                'temperature' => 0.7,
                'max_tokens' => 1000
            ];

            $response = self::sendRequest(self::buildUri($provider), $payload);

            return $response;
        } catch (\Exception $e) {
            \Log::error("AI Query Error: " . $e->getMessage());

            // Fallback to another provider if available
            if ($provider !== 'OpenChat') {
                try {
                    return self::query($text, $conversationId, $use_context, 'OpenChat');
                } catch (\Exception $fallbackError) {
                    throw new AppException("Failed to get response from all providers: " . $fallbackError->getMessage());
                }
            }

            throw new AppException("Failed to process your request: " . $e->getMessage());
        }
    }

    /**
     * Check if user is throttled using cache lock.
     */
    private static function isUserThrottled(int $userId): bool
    {
        $cacheKey = "ai_user_throttle:{$userId}";
        $currentCount = Cache::get($cacheKey, 0);

        if ($currentCount >= self::USER_THROTTLE_LIMIT) {
            return true;
        }

        Cache::put($cacheKey, $currentCount + 1, now()->addSeconds(self::USER_THROTTLE_DURATION));
        return false;
    }

    /**
     * Initialize the provider with rate limiting checks.
     */
    public static function initialize(string $provider): void
    {
        if (!isset(self::$providers[$provider])) {
            throw new AppException("Invalid provider: {$provider}");
        }

        self::$selectedProvider = $provider;
        $apiKey = self::$providers[$provider]['apiKey'];

        if (empty($apiKey)) {
            throw new AppException("Missing API key for provider: {$provider}");
        }

        // Check rate limit
        if (self::isRateLimited($provider)) {
            throw new AppException("Rate limit exceeded for provider: {$provider}. Please try again later.");
        }

        self::$client = new Client([
            'timeout' => 30, // 30 second timeout
            'connect_timeout' => 10 // 10 second connection timeout
        ]);
    }

    /**
     * Check if provider is rate limited.
     */
    private static function isRateLimited(string $provider): bool
    {
        $now = microtime(true);
        $window = 1; // 1 second window

        if (!isset(self::$rateLimits[$provider])) {
            self::$rateLimits[$provider] = [
                'count' => 1,
                'start' => $now
            ];
            return false;
        }

        // Reset if window has passed
        if ($now - self::$rateLimits[$provider]['start'] > $window) {
            self::$rateLimits[$provider] = [
                'count' => 1,
                'start' => $now
            ];
            return false;
        }

        // Increment and check
        self::$rateLimits[$provider]['count']++;

        return self::$rateLimits[$provider]['count'] > self::$providers[$provider]['rateLimit'];
    }

    /**
     * Generate system messages for the API request.
     */
    private static function systemMessages(string $content): array
    {
        $sanitizedContent = PromptEngine::vetInput($content);
        $prompt = PromptEngine::getPromptPrefix($sanitizedContent);
        return [['role' => 'system', 'content' => $prompt]];
    }

    /**
     * Retrieve previous messages for a conversation with pagination.
     */
    public static function getPreviousMessages(int $conversationId, int $limit = 10): array
    {
        try {
            $messages = ModelMessage::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return $messages->map(function ($message) {
                return [
                    'role' => $message->is_model ? 'assistant' : 'user',
                    'content' => PromptEngine::vetInput($message->message),
                ];
            })->reverse()->values()->toArray();
        } catch (\Exception $e) {
            \Log::error("Error fetching previous messages: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Send the API request with retry logic.
     */
    private static function sendRequest(string $uri, array $payload, int $retries = 2): string
    {
        $provider = self::$selectedProvider;
        $lastError = null;

        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            try {
                $response = self::$client->post($uri, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . self::$providers[$provider]['apiKey'],
                        'HTTP-Referer' => 'https://digitwhale.web.app',
                        'X-Title' => 'DigitWhale'
                    ],
                    'json' => $payload,
                    'http_errors' => false
                ]);

                if ($response->getStatusCode() === 429) {
                    // Rate limited - wait and retry
                    sleep(1);
                    continue;
                }

                return self::processResponse($response);
            } catch (RequestException $e) {
                $lastError = $e;
                if ($attempt < $retries) {
                    sleep(1); // Wait before retry
                    continue;
                }
            }
        }

        throw new AppException("API request failed after {$retries} attempts: " .
            ($lastError ? $lastError->getMessage() : "Unknown error"));
    }

    /**
     * Process the API response with better error handling.
     */
    private static function processResponse(ResponseInterface $response): string
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if ($statusCode !== 200) {
            $error = $data['error']['message'] ?? $body;
            throw new AppException("API Error ({$statusCode}): " . substr($error, 0, 200));
        }

        if (empty($data['choices'][0]['message']['content'])) {
            throw new AppException("Empty response from AI provider");
        }

        return $data['choices'][0]['message']['content'];
    }

    /**
     * Build the API request URI with proper URL encoding.
     */
    private static function buildUri(string $provider): string
    {
        $baseUrl = rtrim(self::$providers[$provider]['baseUrl'], '/');
        return $baseUrl;
    }
}
