<?php

namespace App\Modules\FincraModule\Services;

use App\Exceptions\AppException;
use Exception;
use GuzzleHttp\Client;

class FincraService
{
    private static $instance;
    private static $secretKey;
    private $baseUrl = "https://api.paystack.co/";
    private $httpClient;

    // Private constructor for singleton pattern
    private function __construct()
    {
        $this->httpClient = new Client(['base_uri' => $this->baseUrl]);
    }

    // Singleton instance getter
    public static function getInstance(): FincraService
    {

        self::$secretKey = "sk_test_bb3fc97c4a0729e6742033225e4cdef97e231f3f";

        if (!self::$instance) {
            self::$instance = new FincraService();
        }
        return self::$instance;
    }

    // Update the secret key
    public function updateSecretKey(string $secretKey): void
    {
        if (empty($secretKey)) {
            throw new AppException("Paystack secret key cannot be empty.");
        }
        $this->secretKey = $secretKey;
    }

    // Get the secret key or throw an exception if not initialized
    public function getSecretKey(): string
    {
        if (empty($this->secretKey)) {
            throw new AppException("PaystackService is not initialized. Call `initialize()` first.");
        }
        return $this->secretKey;
    }

    // Build authorization headers using the secret key
    private function buildAuthHeader(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getSecretKey(),
            'Content-Type' => 'application/json',
        ];
    }

    // Fetch a list of banks from Paystack's API
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

    // Resolve an account using Paystack's API
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

    // Create a transfer recipient using Paystack's API
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

    // Run a transfer using Paystack's API
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

    // Create a Dedicated Virtual Account (DVA) using Paystack's API
    public function createDVA(string $customer, string $phone, string $provider = 'wema-bank'): array
    {
        $payload = [
            'customer' => $customer,
            'preferred_bank' => $provider,
            'phone' => $phone,
        ];

        try {
            $response = $this->httpClient->post('dedicated_account', [
                'headers' => $this->buildAuthHeader(),
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (Exception $e) {
            throw new AppException("Failed to create DVA: " . $e->getMessage());
        }
    }

    // Create a customer using Paystack's API
    public function createCustomer(array $customer): array
    {
        $payload = [
            'email' => $customer['email'],
            'first_name' => $customer['first_name'] ?? null,
            'last_name' => $customer['last_name'] ?? null,
            'phone' => $customer['phone'] ?? null,
        ];

        try {
            $response = $this->httpClient->post('customer', [
                'headers' => $this->buildAuthHeader(),
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (Exception $e) {
            throw new AppException("Failed to create customer: " . $e->getMessage());
        }
    }

    // Verify a transfer using Paystack's API
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