<?php

namespace App\Gateways\FlutterWave\Services;

use App\Exceptions\AppException;
use App\Helpers\CodeHelper;
use App\Models\AppLog;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use Log;

class FlutterWaveService
{
    public static $sk = "";
    public static $pk = "";

    public static $state = 'development';
    private static $instance;

    private $baseUrl;
    private $httpClient;

    private function __construct()
    {
        $this->baseUrl = "https://api.flutterwave.com/v3/";
        $this->httpClient = new Client(['base_uri' => $this->baseUrl]);
    }

    public static function getInstance(): FlutterWaveService
    {
        if (!self::$instance) {
            self::$instance = new FlutterWaveService();
        }
        return self::$instance;
    }

    private function buildAuthHeader(): array
    {
        return [
            // 'Authorization' => 'Bearer FLWSECK-eac55583f301c5579b19dc5c010c0f13-195fbe8d4cdvt-X',
            'Authorization' => 'Bearer FLWSECK_TEST-5caaa3ed7fae8c6cd7981c4fe910f63a-X',
            'Content-Type' => 'application/json',
        ];
    }

    public function getBanks(): array
    {
        try {
            $response = $this->httpClient->get('banks/NG', ['headers' => $this->buildAuthHeader()]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new AppException($body['message'] ?? 'Failed to fetch banks.');
        } catch (AppException $e) {
            throw new AppException("Failed to fetch banks: " . $e->getMessage());
        }
    }

    public function resolveAccount(string $accountNumber, string $bankCode): array
    {
        try {
            $payload = [
                'account_number' => $accountNumber,
                'account_bank' => $bankCode,
            ];

            $response = $this->httpClient->post("accounts/resolve", [
                'headers' => $this->buildAuthHeader(),
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody(), true);

            if (!$data['status']) {
                throw new AppException($data['message'] ?? 'Unknown error');
            }

            return $data;
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new AppException($body['message'] ?? 'Failed to resolve account.');
        } catch (AppException $e) {
            $errorMessage = CodeHelper::extractErrorMessage($e);
            throw new AppException($errorMessage);
        }
    }

    public function createTransferRecipient($account_number, $bank_code): array
    {
        try {
            $payload = [
                "account_bank" => $bank_code,
                "account_number" => $account_number,
                "beneficiary_name" => "Whale Beneficiary",
                "currency" => "NGN",
                "bank_name" => "Whale Resolve",
            ];

            $response = $this->httpClient->post('beneficiaries', [
                'headers' => $this->buildAuthHeader(),
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody(), true);

            if (!$data['status']) {
                throw new AppException("Failed to create recipient: " . ($data['message'] ?? 'Unknown error'));
            }

            return $data;
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new AppException($body['message'] ?? 'Failed to create recipient.');
        } catch (AppException $e) {
            throw new AppException("Failed to create recipient: " . $e->getMessage());
        }
    }

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
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new AppException($body['message'] ?? 'Failed to run transfer.');
        } catch (AppException $e) {
            Log::error($e);
            throw new AppException("Failed to run transfer: " . $e->getMessage());
        }
    }

    public function createDVA(string $email, string $txRef, string $phoneNumber, string $firstName, string $lastName, string $narration, string $bvn, bool $isPermanent = true): array
    {
        $payload = [
            "email" => $email,
            "tx_ref" => $txRef,
            "phonenumber" => $phoneNumber,
            "firstname" => $firstName,
            "lastname" => $lastName,
            "account_name" => $firstName . ' ' . $lastName,
            "narration" => $firstName . ' ' . $lastName,
            "bvn" => $bvn,
            "is_permanent" => true,
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
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new AppException($body['message'] ?? 'Failed to create DVA.');
        } catch (AppException $e) {
            Log::info($e->getMessage());
            throw new AppException("Failed to create DVA: " . $e->getMessage());
        }
    }

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
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new AppException($body['message'] ?? 'Failed to create customer.');
        } catch (AppException $e) {
            throw new AppException("Failed to create customer: " . $e->getMessage());
        }
    }

    public function verifyTransfer(string $reference): array
    {
        try {
            $response = $this->httpClient->get("transactions/verify_by_reference?tx_ref=$reference", [
                'headers' => $this->buildAuthHeader(),
            ]);

            $data = json_decode($response->getBody(), true);

            if (!$data['status']) {
                throw new AppException("Failed to verify transfer: " . ($data['message'] ?? 'Unknown error'));
            }

            return $data;
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new AppException($body['message'] ?? 'Failed to verify transfer.');
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
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new AppException($body['message'] ?? 'Failed to generate link.');
        } catch (AppException $e) {
            throw new AppException("Failed to generate link: " . $e->getMessage());
        }
    }

    public function verifyTransaction(string $reference): array
    {
        try {
            $response = $this->httpClient->get("transactions/verify_by_reference?tx_ref=" . $reference, [
                'headers' => $this->buildAuthHeader(),
            ]);

            $data = json_decode($response->getBody(), true);

            if (($data['status'] ?? '') === 'error') {
                throw new AppException($data['message'] ?? 'Unknown error');
            }

            return $data['data'];
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new AppException($body['message'] ?? 'Failed to verify payment.');
        } catch (Exception $e) {
            throw new AppException("Failed to verify payment: " . $e->getMessage());
        }
    }

    /**
     * @throws AppException
     */
    public function getWalletBalance(): array
    {
        try {
            $response = $this->httpClient->get("balances", [
                'headers' => $this->buildAuthHeader(),
            ]);

            $value = json_decode($response->getBody(), true);
            return $value['data'];
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new AppException($body['message'] ?? 'Failed to verify payment.');
        } catch (GuzzleException $e) {
            throw new AppException("Failed to fetch wallet balance: " . $e->getMessage());
        }
    }

    /// BILL PAYMENTS


    public function getBillCategories(string $country = "NG"): mixed
    {
        try {
            $response = $this->httpClient->get("top-bill-categories?country=" . $country, [
                'headers' => $this->buildAuthHeader(),
            ]);

            $value = json_decode($response->getBody(), true);
            return $value['data'];
        } catch (AppException $e) {
            throw new AppException("Failed to fetch wallet balance: " . $e->getMessage());
        } catch (GuzzleException $e) {
            throw new AppException("Failed to fetch wallet balance: " . $e->getMessage());
        }
    }

    public function getBillerByCategory(string $catagory, string $country = "NG"): mixed
    {
        try {
            $response = $this->httpClient->get("bills/" . $catagory . "/billers?country=" . $country, [
                'headers' => $this->buildAuthHeader(),
            ]);

            $value = json_decode($response->getBody(), true);
            return $value['data'];
        } catch (AppException $e) {
            throw new AppException("Failed to fetch wallet balance: " . $e->getMessage());
        } catch (GuzzleException $e) {
            throw new AppException("Failed to fetch wallet balance: " . $e->getMessage());
        }
    }

    public function getBillerItems(string $biller_code): mixed
    {
        try {
            $endpoint = "billers/" . $biller_code . "/items";

            $response = $this->httpClient->get($endpoint, [
                'headers' => $this->buildAuthHeader(),
            ]);

            $value = json_decode($response->getBody(), true);
            return $value['data'];
        } catch (AppException $e) {
            throw new AppException("Failed to fetch wallet balance: " . $e->getMessage());
        } catch (GuzzleException $e) {
            throw new AppException("Failed to fetch wallet balance: " . $e->getMessage());
        }
    }

    public function validateUserInformation(string $item_id, string $biller_code, string $customer_id): mixed
    {
        try {

            $response = $this->httpClient->get("bill-items/" . $item_id . "/validate?code=" . $biller_code . "&customer=" . $customer_id, [
                'headers' => $this->buildAuthHeader(),
            ]);

            $value = json_decode($response->getBody(), true);
            return $value['data'];
        } catch (AppException $e) {
            throw new AppException("Failed to fetch wallet balance: " . $e->getMessage());
        } catch (GuzzleException $e) {
            throw new AppException("Failed to fetch wallet balance: " . $e->getMessage());
        }
    }

    public function payUtilityBill(string $item_id, string $biller_code, string $amount, string $customer)
    {
        try {

            $payload = [
                "country" => "NG",
                "customer_id" => $customer,
                "amount" => $amount,
                "reference" => CodeHelper::generateSecureReference()
            ];

            $response = $this->httpClient->post("billers/{$biller_code}/items/{$item_id}/payment", [
                'headers' => $this->buildAuthHeader(),
                'json' => $payload,
            ]);

            $value = json_decode($response->getBody()->getContents(), true);
            AppLog::debug("payUtilityBill", $value);
            return $value;


            // Mock response for testing
            // return [
            //     'event' => 'singlebillpayment.status',
            //     'event.type' => 'SingleBillPayment',
            //     'data' => [
            //         'customer' => $customer,
            //         'amount' => (float) $amount,
            //         'network' => 'MTN',
            //         'tx_ref' => CodeHelper::generateSecureReference(),
            //         'flw_ref' => CodeHelper::generate(30, true),
            //         'batch_reference' => null,
            //         'customer_reference' => 'DigitWhale-' . substr(md5(uniqid()), 0, 14),
            //         'status' => 'success',
            //         'message' => 'Bill Payment was completed successfully',
            //         'reference' => null,
            //     ],
            // ];
        } catch (AppException $e) {
            throw new AppException("Failed to pay utility bill: " . $e->getMessage());
        } catch (GuzzleException $e) {
            throw new AppException("Provider communication error: " . $e->getMessage());
        }
    }


}
