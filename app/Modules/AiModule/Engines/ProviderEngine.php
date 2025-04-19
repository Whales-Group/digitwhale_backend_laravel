<?php

namespace App\Modules\AiModule\Engines;

use App\Exceptions\AppException;
use App\Models\ModelMessage;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\ResponseInterface;

class ProviderEngine
{
    private const USER_THROTTLE_LIMIT = 5;
    private const USER_THROTTLE_DURATION = 7;
    private const API_KEY = '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';

    public static array $providers = [
        'OpenChat' => [
            'baseUrl' => 'https://openrouter.ai/api/v1/chat/completions',
            'apiKey' => self::API_KEY,
            'model' => 'OpenChat/OpenChat-7b:free',
            'rateLimit' => 5
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
        'GeminiRaw' => [
            'baseUrl' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
            'apiKey' => 'AIzaSyBm8yhyqo3DwQEoM6N34zyI25Lxg4US45Y',
            'rateLimit' => 5
        ],
    ];

    private static Client $client;
    private static string $selectedProvider;
    private static array $rateLimits = [];

    /**
     * @throws AppException
     */
    public static function query(string $text, ?int $conversationId, ?bool $use_context = true, string $provider): string
    {
        if (!$userId = Auth::id()) {
            throw new AppException("Authentication required");
        }

        if (self::isUserThrottled($userId)) {
            throw new AppException("Too many requests. Please wait " . self::USER_THROTTLE_DURATION . " seconds before trying again.");
        }

        try {
            $payload = self::buildPayload($text, $conversationId, $use_context, $provider);
            return self::sendRequest(self::buildUri($provider), $payload);
        } catch (\Exception $e) {
            throw new AppException("Failed to process your request: " . $e->getMessage());
        }
    }

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

    private static function buildPayload(string $text, ?int $conversationId, bool $use_context, string $provider): array
    {
        if ($provider === 'GeminiRaw') {
            return [
                "system_instruction" => [
                    "parts" => $use_context ? self::systemMessages($text, $provider) : [],
                ],
                'contents' => array_merge(
                    $use_context && $conversationId ? self::getPreviousMessages($conversationId, 10, $provider) : [],
                    [
                        [
                            'role' => 'user',
                            "parts" => [
                                [
                                    'text' => PromptEngine::vetInput($text)
                                ]
                            ]
                        ]
                    ]
                )
            ];
        }

        return [
            'model' => self::$providers[$provider]['model'],
            'messages' => array_merge(
                $use_context ? self::systemMessages($text, $provider) : [],
                $use_context && $conversationId ? self::getPreviousMessages($conversationId, 10, $provider) : [],
                [['role' => 'user', 'content' => PromptEngine::vetInput($text)]]
            ),
            'temperature' => 0.7,
            'max_tokens' => 1000
        ];
    }

    private static function systemMessages(string $content, string $provider): array
    {
        $sanitizedContent = PromptEngine::vetInput($content);
        $prompt = PromptEngine::getPromptPrefix($sanitizedContent);

        if ($provider === 'GeminiRaw') {
            return
                [
                    ["text" => $prompt]
                ];
        }

        return [
            [
                'role' => 'system',
                'content' => $prompt
            ]
        ];
    }

    public static function getPreviousMessages(int $conversationId, int $limit = 10, string $provider = 'OpenAI'): array
    {
        try {
            $messages = ModelMessage::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return $messages->map(function ($message) use ($provider) {
                $sanitized = PromptEngine::vetInput($message->message);

                if ($provider === "GeminiRaw") {
                    return [
                        "role" => $message->is_model ? 'model' : 'user',
                        "parts" => [
                            ["text" => $sanitized]
                        ]
                    ];
                } else {
                    return [
                        'role' => $message->is_model ? 'assistant' : 'user',
                        'content' => $sanitized,
                    ];
                }
            })->reverse()->values()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private static function sendRequest(string $uri, array $payload, int $retries = 2): string
    {
        $provider = self::$selectedProvider;
        $lastError = null;


        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            try {
                $queryParam = ($provider === 'GeminiRaw') ? ('?key=' . self::$providers[$provider]['apiKey']) : '';
                $headers = [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . self::$providers[$provider]['apiKey'],
                    'HTTP-Referer' => 'https://digitwhale.web.app',
                    'X-Title' => 'DigitWhale'
                ];

                if ($provider === 'GeminiRaw') {
                    unset($headers['Authorization']);
                }


                $response = self::$client->post($uri . $queryParam, [
                    'headers' => $headers,
                    'json' => $payload,
                    'http_errors' => false
                ]);


                if ($response->getStatusCode() === 429) {
                    sleep(1);
                    continue;
                }

                return self::processResponse($response);
            } catch (RequestException $e) {
                $lastError = $e;
                if ($attempt < $retries) {
                    sleep(1);
                    continue;
                }
            }
        }

        throw new AppException("API request failed after {$retries} attempts: " .
            ($lastError ? $lastError->getMessage() : "Unknown error"));
    }

    private static function processResponse(ResponseInterface $response): string
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        $provider = self::$selectedProvider;

        if ($statusCode !== 200) {
            $error = $data['error']['message'] ?? $body;
            throw new AppException("API Error ({$statusCode}): " . substr($error, 0, 200));
        }
        $providerResponse = $provider === 'GeminiRaw'
            ? $data['candidates'][0]['content']['parts'][0]['text']
            : ($data['choices'][0]['message']['content'] ?? throw new AppException("Unexpected API response format"));
        return $providerResponse;
    }

    private static function buildUri(string $provider): string
    {
        return self::$providers[$provider]['baseUrl'];
    }

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

        if (self::isRateLimited($provider)) {
            throw new AppException("Rate limit exceeded for provider: {$provider}. Please try again later.");
        }

        self::$client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10
        ]);
    }

    private static function isRateLimited(string $provider): bool
    {
        $now = microtime(true);
        $window = 1;

        if (!isset(self::$rateLimits[$provider])) {
            self::$rateLimits[$provider] = ['count' => 1, 'start' => $now];
            return false;
        }

        if ($now - self::$rateLimits[$provider]['start'] > $window) {
            self::$rateLimits[$provider] = ['count' => 1, 'start' => $now];
            return false;
        }

        self::$rateLimits[$provider]['count']++;
        return self::$rateLimits[$provider]['count'] > self::$providers[$provider]['rateLimit'];
    }
}
