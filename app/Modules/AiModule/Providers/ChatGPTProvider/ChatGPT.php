<?php

namespace App\Modules\AiModule\Providers\GeminiProvider; 

use App\Exceptions\AppException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ChatGPT
{
    private static ?ChatGPT $instance = null;
    private static ?string $secretKey = null;
    private string $baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent";
    private Client $client;

    private function __construct()
    {
        $this->client = new Client(['base_uri' => $this->baseUrl]);
    }

    public static function getInstance(): ChatGPT
    {
        if (self::$instance === null) {
            self::$instance = new ChatGPT();
        }
        return self::$instance;
    }

    public function updateSecretKey(string $secretKey): void
    {
        if (empty($secretKey)) {
            throw new AppException("ChatGPT secret key cannot be empty.");
        }
        self::$secretKey = $secretKey;
    }

    public function getSecretKey(): string
    {
        if (empty(self::$secretKey)) {
            throw new AppException("ChatGPT is not initialized. Call `getInstance()` and `updateSecretKey()` first.");
        }
        return self::$secretKey;
    }

    private function buildAuthHeader(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getSecretKey(),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Sends a request to the ChatGPT API.
     *
     * @param array $payload The request payload.
     * @return array The API response as an associative array.
     * @throws AppException If the API request fails.
     */
    public function sendRequest(array $payload): array
    {
        try {
            $response = $this->client->post([
                'query' => ['key' => $this->getSecretKey()],
            ], [
                'headers' => $this->buildAuthHeader(),
                'json' => $payload,
            ]);

            $body = $response->getBody()->getContents();
            return json_decode($body, true);
        } catch (GuzzleException $e) {
            throw new AppException("ChatGPT API request failed: " . $e->getMessage());
        } catch (\JsonException $e) {
            throw new AppException("Failed to decode JSON response from ChatGPT API: " . $e->getMessage());
        }
    }
    
}