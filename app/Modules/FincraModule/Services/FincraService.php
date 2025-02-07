<?php

namespace App\Modules\FincraModule\Services;

use App\Exceptions\AppException;
use Exception;
use GuzzleHttp\Client;

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
            $response = $this->httpClient->get('bank', ['headers' => $this->buildAuthHeader()]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (Exception $e) {
            throw new AppException("Failed to fetch banks: " . $e->getMessage());
        }
    }

    // Resolve an account using Fincra's API
    public function resolveAccount(string $accountNumber, string $bankCode): array
    {
        try {
            $query = http_build_query([
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ]);
            $response = $this->httpClient->get("bank/resolve?$query", ['headers' => $this->buildAuthHeader()]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (Exception $e) {
            throw new AppException("Failed to resolve account: " . $e->getMessage());
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
        } catch (Exception $e) {
            throw new AppException("Failed to create recipient: " . $e->getMessage());
        }
    }

    // Run a transfer using Fincra's API
    public function runTransfer(array $payload): array
    {
        try {
            $response = $this->httpClient->post('transfer', [
                'headers' => $this->buildAuthHeader(),
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (Exception $e) {
            throw new AppException("Failed to run transfer: " . $e->getMessage());
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            throw new AppException("Failed to verify transfer: " . $e->getMessage());
        }
    }
}