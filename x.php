<?php

namespace App\Modules\AiModule\Providers\GeminiProvider;

use App\Exceptions\AppException;
use App\Models\Account;
use App\Models\TransactionEntry;
use App\Models\User;
use App\Modules\AiModule\Engines\BaseEngine;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Auth;

class Gemini
{
  private static string $baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent";
  private static string $apiKey;
  private static Client $client;
  private static array $protectedModels = [
    Account::class,
    User::class,
    TransactionEntry::class,
  ];

  public static function initialize(): void
  {
    self::$apiKey = "AIzaSyAWbUp1o2JNbAUm8o-SBDXpjohyHZ0SpAE";
    if (empty(self::$apiKey)) {
      throw new AppException("API key not set in environment variables.");
    }
  }

  public static function query(string $text): mixed
  {
    if (empty(trim($text))) {
      throw new AppException("Text input cannot be empty.");
    }

    self::ensureClientInitialized();
    $context = self::generateAiContext();
    $fullText = "$context$text";
    $payload = self::buildPayload($fullText);
    self::initialize();
    $uri = self::buildUri();

    return self::sendRequest($uri, $payload);
  }

  private static function ensureClientInitialized(): void
  {
    if (!isset(self::$client)) {
      self::$client = new Client();
    }
  }

  private static function buildPayload(string $text): array
  {
    $fullText = self::getPromptPrefix($text) . " " . $text;
    return [
      "contents" => [
        ["parts" => [["text" => $fullText]]]
      ]
    ];
  }

  private static function buildUri(): string
  {
    return self::$baseUrl . "?key=" . self::$apiKey;
  }

  private static function sendRequest(string $uri, array $payload): mixed
  {
    try {
      $response = self::$client->post($uri, [
        'headers' => ['Content-Type' => 'application/json'],
        'json' => $payload,
      ]);
      return self::processResponse($response);
    } catch (RequestException $e) {
      throw new AppException("API request failed: " . ($e->hasResponse() ? $e->getResponse()->getStatusCode() : "Unable to connect."));
    }
  }

  private static function processResponse(\Psr\Http\Message\ResponseInterface $response): mixed
  {
    $body = $response->getBody()->getContents();
    $decodedResponse = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new AppException("Failed to decode response from the service.");
    }

    if ($response->getStatusCode() !== 200) {
      throw new AppException("Failed to generate content: Request was not successful.");
    }

    $content = $decodedResponse['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $sensitiveFields = self::getSensitiveFields();
    foreach ($sensitiveFields as $field) {
        if (stripos($content, $field) !== false) {
            throw new AppException("Response contains restricted information.");
        }
    }

    return $content;
  }

  private static function getPromptPrefix(string $text): string
  {
    $prefix = "
  " . BaseEngine::$IamString . "
  
  
  ### General Guidelines:
  " . self::formatClaves(BaseEngine::generalGuidelines(self::getSensitiveFields())) . "
  
  " . (self::isAlignedWithGoals($text) ? '' : self::getSarcasticJoke()) . "
  
  ### Response Tone & Format:
  " . self::formatClaves(BaseEngine::responseToneAndFormat()) . "
  
  ### Support & Contact Information:
  " . self::formatClaves(BaseEngine::supportInformation()) . "
  
  ### Platform Features:
  " . self::formatClaves(BaseEngine::platformFeatures()) . "
  
  ### Supported Currencies:
  DigitWhale currently supports only **one currency per transaction**:
  " . self::formatClaves(BaseEngine::supportedCurrencies()) . "
  
  ### Account Limitations:
  - Each user is allowed **a maximum of 3 accounts**.
  
  Now, please process and respond to the following query:";

    return $prefix;
  }

  private static function isAlignedWithGoals(string $text): bool
  {
    $goals = BaseEngine::$IamString; // Assuming this contains goals/directives
    return stripos($text, $goals) !== false; // Simple check; enhance as needed
  }


  private static function getSarcasticJoke(): string
  {
    return "
  Oh, looks like someone tried to sneak a square peg into " . BaseEngine::$IamString . "'s round hole! 
  Here’s a tip: I’m built to solve *specific* problems, not to entertain off-topic quests—try again, champ!
  ";
  }

  private static function getSensitiveFields(): array
  {
    $sensitiveFields = [];
    foreach (self::$protectedModels as $modelClass) {
      if (property_exists($modelClass, 'promptProtect')) {
        $sensitiveFields = array_merge($sensitiveFields, $modelClass::$promptProtect);
      }
    }
    return ($sensitiveFields);
  }

  private static function getUserContext(): array
  {
    $user = Auth::user();

    if (!$user) {
      return [
        'account' => '',
        'user' => '',
        'transactions' => '',
      ];
    }

    $context = [
      'account' => '',
      'user' => '',
      'transactions' => '',
    ];

    $accounts = Account::where("user_id", $user->id)->get();
    if ($accounts->isNotEmpty()) {
      $accountsData = $accounts->toArray();
      $context['account'] = "The user's account information: " . json_encode($accountsData) . ". ";
    }

    $profile = User::where("id", $user->id)->first();
    if ($profile) {
      $profileData = $profile->toArray();
      $context['user'] = "The user's information: " . json_encode($profileData) . ". ";
    }

    if ($accounts->isNotEmpty()) {
      $accountIds = $accounts->pluck('id')->toArray();

      $transactions = TransactionEntry::where(function ($query) use ($accountIds) {
        $query->whereIn('from_sys_account_id', $accountIds)
          ->orWhereIn('to_sys_account_id', $accountIds);
      })
        ->orderBy('created_at', 'desc')
        ->take(6)
        ->get();

      if ($transactions->isNotEmpty()) {
        $transactionsData = $transactions->toArray();
        $context['transactions'] = "The user's recent transactions: " . json_encode($transactionsData) . ". ";
      }
    }

    return $context;
  }

  public static function generateAiContext(): string
  {
    $context = self::getUserContext();
    $fullContext = $context['user'] . $context['account'] . $context['transactions'];
    return $fullContext;
  }

  private static function formatClaves(
    array $items,
    string $trueText = "✅ Supported",
    string $falseText = "❌ Not supported yet",
    string $prefix = " - **",
    string $suffix = "**"
  ): string {
    if (empty($items)) {
      return "";
    }

    return implode("\n", array_map(function ($key, $value) use ($trueText, $falseText, $prefix, $suffix) {
      $status = $value ? $trueText : $falseText;
      return "{$prefix}{$key}{$suffix} {$status}";
    }, array_keys($items), $items));
  }
}