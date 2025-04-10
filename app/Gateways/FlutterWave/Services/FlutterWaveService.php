<?php

namespace App\Gateways\FlutterWave\Services;

use App\Exceptions\AppException;
use App\Helpers\CodeHelper;
use GuzzleHttp\Client;
use Log;

class FlutterWaveService
{
    public static $sk = "";
    public static $pk = "";


    public static $state = 'development';
    private static $instance;

    private $baseUrl;

    private $httpClient;

    // Private constructor for singleton pattern
    private function __construct()
    {
        $this->baseUrl = "https://api.flutterwave.com/v3/";
        $this->httpClient = new Client(['base_uri' => $this->baseUrl]);
    }

    // Singleton instance getter
    public static function getInstance(): FlutterWaveService
    {

        if (!self::$instance) {
            self::$instance = new FlutterWaveService();
        }
        return self::$instance;
    }

    // Build authorization headers using the secret key
    private function buildAuthHeader(): array
    {
        return [
            'Authorization' => 'Bearer FLWSECK-eac55583f301c5579b19dc5c010c0f13-195fbe8d4cdvt-X',
            'Content-Type' => 'application/json',
        ];
    }

    // Fetch a list of banks from Fincra's API
    public function getBanks(): array
    {
        try {
            $response = $this->httpClient->get('banks/NG', ['headers' => $this->buildAuthHeader()]);
            $data = json_decode($response->getBody(), true);
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
            $response = $this->httpClient->post('beneficiaries', [
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
            $response = $this->httpClient->post('transfers', [
                'headers' => $this->buildAuthHeader(),
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody(), true);

            if (!$data['status']) {
                throw new AppException("Failed to run transfer: " . ($data['message'] ?? 'Unknown error'));
            }

            return $data;
        } catch (AppException $e) {
            Log::error($e);
            throw new AppException("Failed to run transfer: " . $e->getMessage());
        }
    }

    // Create a Dedicated Virtual Account (DVA) using Paystack's API
    public function createDVA(string $email, string $txRef, string $phoneNumber, string $firstName, string $lastName, string $narration, string $bvn, bool $isPermanent = true, ): array
    {
        $payload = [
            "email" => $email,
            "tx_ref" => $txRef,
            "phonenumber" => $phoneNumber,
            "is_permanent" => $isPermanent,
            "firstname" => $firstName,
            "lastname" => $lastName,
            "narration" => $narration,
            "bvn" => $bvn,
        ];

        try {
            $response = $this->httpClient->post('virtual-account-numbers', [
                'headers' => $this->buildAuthHeader(),
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody(), true);
            
            if (!$data['status']) {
                throw new AppException("Failed to create DVA: " . ($data['message'] ?? 'Unknown error'));
            }
            
            return $data;
        } catch (AppException $e) {
            Log::info($e->getMessage());
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

    public function verifyPayment(string $reference, ): array
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
    /// BILL PAYMENTS
    public function getNetworkBillers()
    {
    }
    public function getUtilityBillers()
    {
    }
    public function payNetworkBill()
    {
    }
    public function payUtilityBill()
    {
    }
}