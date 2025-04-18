<?php

namespace App\Gateways\Paystack\Services;

use App\Helpers\CodeHelper;
use App\Exceptions\AppException;
use GuzzleHttp\Client;

class PaystackService
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
    public static function getInstance(): PaystackService
    {
        if (!self::$instance) {
            self::$instance = new PaystackService();
        }

        // Set a default secret key if not already set
        if (empty(self::$secretKey)) {
            self::$secretKey = "sk_live_94ebb16f339b52d63dc7f47b64d08dc897b74e23";
        }

        return self::$instance;
    }

    // Update the secret key
    public function updateSecretKey(string $secretKey): void
    {
        if (empty($secretKey)) {
            throw new AppException("Paystack secret key cannot be empty.");
        }
        self::$secretKey = $secretKey;
    }

    // Get the secret key or throw an exception if not initialized
    public function getSecretKey(): string
    {
        if (empty(self::$secretKey)) {
            throw new AppException("PaystackService is not initialized. Call `getInstance()` first.");
        }
        return self::$secretKey;
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

            if (!$data['status']) {
                throw new AppException("Failed to fetch banks: " . ($data['message'] ?? 'Unknown error'));
            }

            return $data;
        } catch (AppException $e) {
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

            if (!$data['status']) {
                throw new AppException($data['message'] ?? 'Unknown error');
            }

            return $data;
        } catch (AppException $e) {
            $errorMessage = CodeHelper::extractErrorMessage($e);
            throw new AppException($errorMessage);
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

            if (!$data['status']) {
                throw new AppException("Failed to create recipient: " . ($data['message'] ?? 'Unknown error'));
            }

            return $data;
        } catch (AppException $e) {
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

            if (!$data['status']) {
                throw new AppException("Failed to run transfer: " . ($data['message'] ?? 'Unknown error'));
            }

            return $data;
        } catch (AppException $e) {
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

            if (!$data['status']) {
                throw new AppException("Failed to create DVA: " . ($data['message'] ?? 'Unknown error'));
            }

            return $data;
        } catch (AppException $e) {
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

            if (!$data['status']) {
                throw new AppException("Failed to create customer: " . ($data['message'] ?? 'Unknown error'));
            }

            return $data;
        } catch (AppException $e) {
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

            if (!$data['status']) {
                throw new AppException("Failed to verify transfer: " . ($data['message'] ?? 'Unknown error'));
            }

            return $data;
        } catch (AppException $e) {
            throw new AppException("Failed to verify transfer: " . $e->getMessage());
        }
    }

    public function generatePaymentLink(string $email, string $amount): array
    {
        try {
            $payload = [
                "email" => $email,
                "amount" => $amount,
            ];
            $response = $this->httpClient->post("transaction/initialize", [
                'headers' => $this->buildAuthHeader(),
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody(), true);

            if (!$data['status']) {
                throw new AppException("Failed to generate link: " . ($data['message'] ?? 'Unknown error'));
            }

            return $data['data'];
        } catch (AppException $e) {
            throw new AppException("Failed to generate link: " . $e->getMessage());
        }
    }

    public function verifyPayment(string $reference,): array
    {
        try {
            $response = $this->httpClient->get("transaction/verify/$reference", [
                'headers' => $this->buildAuthHeader(),
            ]);

            $data = json_decode($response->getBody(), true);

            if (!$data['status']) {
                throw new AppException("Failed to verify payment: " . ($data['message'] ?? 'Unknown error'));
            }

            return $data['data'];
        } catch (AppException $e) {
            throw new AppException("Failed to verify payment: " . $e->getMessage());
        }
    }
    public function getWalletBalance(): array
    {
        try {
            $response = $this->httpClient->get("/balance", [
                'headers' => $this->buildAuthHeader(),
            ]);

            $value = json_decode($response->getBody(), true);
            return $value['data'];
        } catch (AppException $e) {
            throw new AppException("Failed to fetch wallet balance: " . $e->getMessage());
        }
    }
}