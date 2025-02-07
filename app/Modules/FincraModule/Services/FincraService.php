<?php

namespace App\Modules\FincraModule\Services;

use App\Common\Enums\Cred;
use App\Common\Enums\Currency;
use App\Common\Enums\TransferType;
use App\Common\Helpers\CodeHelper;
use App\Exceptions\AppException;
use GuzzleHttp\Client;
use Log;

class FincraService
{
    private static $instance;
    private static $secretKey;
    private $baseUrl = "https://sandboxapi.fincra.com/";
    // private $baseUrl = "https://api.fincra.com/";

    private $httpClient;

    // Private constructor for singleton pattern
    private function __construct()
    {
        $this->httpClient = new Client(['base_uri' => $this->baseUrl]);
    }

    // Singleton instance getter
    public static function getInstance(): FincraService
    {

        self::$secretKey = "1lWm8PZgyRaDJ3lXUqM5UJc1ZguvarNY";

        if (!self::$instance) {
            self::$instance = new FincraService();
        }
        return self::$instance;
    }

    // Update the secret key
    public function updateSecretKey(string $secretKey): void
    {
        if (empty($secretKey)) {
            throw new AppException("Fincra secret key cannot be empty.");
        }
        $this->secretKey = $secretKey;
    }

    // Get the secret key or throw an exception if not initialized
    public function getSecretKey(): string
    {
        if (empty($this->secretKey)) {
            throw new AppException("FincraService is not initialized. Call `initialize()` first.");
        }
        return $this->secretKey;
    }

    // Build authorization headers using the secret key
    private function buildAuthHeader(): array
    {
        return [
            'api-key' => "1lWm8PZgyRaDJ3lXUqM5UJc1ZguvarNY",
            'Content-Type' => 'application/json',
        ];
    }

    // Fetch a list of banks from Fincra's API
    public function getBanks(): array
    {
        try {
            $response = $this->httpClient->get('/core/banks?currency=NGN&country=NG', ['headers' => $this->buildAuthHeader()]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (AppException $e) {
            throw new AppException("Failed to fetch banks: " . $e->getMessage());
        }
    }

    // Resolve an account using Fincra's API
    public function resolveAccount(string $accountNumber, string $bankCode): array
    {
        try {
            $payload = [
                'accountNumber' => $accountNumber,
                'bankCode' => $bankCode,
                "type" => "nuban"
            ];

            $response = $this->httpClient->post(
                "/core/accounts/resolve",
                [
                    'headers' => $this->buildAuthHeader(),
                    'json' => $payload,
                ]
            );

            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (AppException $e) {
            $errorMessage = CodeHelper::extractErrorMessage($e);
            throw new AppException($errorMessage);
        }
    }

    // Create a transfer recipient using Fincra's API
    public function createTransferRecipient(array $payload): array
    {
        try {
            $response = $this->httpClient->post('transferrecipient', [
                'headers' => $this->buildAuthHeader(),
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (AppException $e) {
            throw new AppException("Failed to create recipient: " . $e->getMessage());
        }
    }

    // Run a transfer using Fincra's API
    public function runTransfer(TransferType $transferType, array $payload): array
    {
        try {
            switch ($transferType) {
                case TransferType::BANK_ACCOUNT_TRANSFER:
                    return $this->performNGNTransfer($payload);
                default:
                    throw new AppException("Transfer Not avaliable for Specified Currency");
            }
        } catch (AppException $e) {
            throw new AppException("Failed to run transfer: " . $e->getMessage());
        }
    }


    private function performNGNTransfer(array $payload): mixed
    {
        $body = [
            "amount" => $payload['amount'],
            "beneficiary" => [
                "accountHolderName" => $payload['accountHolderName'],
                "accountNumber" => $payload['accountHolderName'],
                "bankCode" => $payload['bankCode'],
                // "country" => "NG", 
                // for chosing which country the sender lives in, 
                // in ISO regilated format
                "firstName" => $payload['firstName'],
                "lastName" => $payload['lastName'],
                "type" => $payload['type'],
            ],
            "business" => Cred::BUSINESS_ID,
            "customerReference" => CodeHelper::generateSecureReference(),
            "description" => $payload['description'],
            "destinationCurrency" => "NGN",
            "paymentDestination" => "bank_account",
            "sourceCurrency" => "NGN",
            "sender" => [
                "name" => $payload['sender_name'],
                "email" => $payload['sender_email'],
            ]
        ];

        try {
            $response = $this->httpClient->post('/disbursements/payouts', [
                'headers' => $this->buildAuthHeader(),
                'json' => $body,
            ]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (AppException $e) {
            throw new AppException("Failed to create DVA: " . $e->getMessage());
        }
    }

    // Create a Dedicated Virtual Account (DVA) using Fincra's API
    // wema, providus, globus
    public function createDVA(
        string $dateOfBirth,
        string $firstName,
        string $lastName,
        string $bvn,
        string $bank = 'wema',
        string $currency,
        string $email

    ): array {
        $payload = [
            "dateOfBirth" => $dateOfBirth /*"10-12-1993"*/ ,
            "accountType" => "individual",
            "currency" => $currency ?? "NGN",
            "KYCInformation" => [
                "firstName" => $firstName,
                "lastName" => $lastName,
                "email" => $email,
                "bvn" => $bvn
            ],
            "channel" => $bank
        ];

        try {
            $response = $this->httpClient->post('/profile/virtual-accounts/requests', [
                'headers' => $this->buildAuthHeader(),
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (AppException $e) {
            throw new AppException("Failed to create DVA: " . $e->getMessage());
        }
    }

    // Verify a transfer using Fincra's API
    public function verifyTransfer(string $reference): array
    {
        try {
            $response = $this->httpClient->get("transfer/verify/$reference", [
                'headers' => $this->buildAuthHeader(),
            ]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (AppException $e) {
            throw new AppException("Failed to verify transfer: " . $e->getMessage());
        }
    }
}