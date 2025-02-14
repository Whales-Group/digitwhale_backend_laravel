<?php

namespace App\Modules\FincraModule\Services;

use App\Common\Enums\Cred;
use App\Common\Enums\Currency;
use App\Common\Enums\TransferType;
use App\Common\Helpers\CodeHelper;
use App\Exceptions\AppException;
use Error;
use GuzzleHttp\Client;
use Log;

class FincraService
{
    public static $state = 'development';
    private static $instance;

    private $baseUrl;

    private $httpClient;

    // Private constructor for singleton pattern
    private function __construct()
    {
        // $this->baseUrl = "https://sandboxapi.fincra.com/";
        $this->baseUrl = "https://api.fincra.com/";

        $this->httpClient = new Client(['base_uri' => $this->baseUrl]);
    }

    // Singleton instance getter
    public static function getInstance(): FincraService
    {

        if (!self::$instance) {
            self::$instance = new FincraService();
        }
        return self::$instance;
    }

    // Build authorization headers using the secret key
    private function buildAuthHeader(): array
    {
        return [
            'api-key' => 'S2OWmj2VdpXeXE8ipngIVEBtk8LfFFyc',
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

    // Run a transfer using Fincra's API
    public function runTransfer(TransferType $transferType, array $payload): array
    {
        try {
            switch ($transferType) {
                case TransferType::BANK_ACCOUNT_TRANSFER:
                    if (request()->beneficiary_type != 'individual') {
                        return $this->performNGNTransferToCorporateAccount($payload);
                    }
                    return $this->performNGNTransferToPersonalAccount($payload);
                default:
                    throw new AppException("Transfer Not avaliable for Specified Currency");
            }
        } catch (AppException $e) {
            throw new AppException("Failed to run transfer: " . $e->getMessage());
        }
    }

    private function performNGNTransferToPersonalAccount(array $payload): mixed
    {
        $requiredPayload = [
            "amount" => $payload['amount'],
            "beneficiary" => [
                "accountHolderName" => $payload['beneficiary']['accountHolderName'],
                "accountNumber" => $payload['beneficiary']['accountNumber'],
                "bankCode" => $payload['beneficiary']['bankCode'],
                "firstName" => $payload['beneficiary']['firstName'],
                "lastName" => $payload['beneficiary']['lastName'],
                "type" => $payload['beneficiary']['type'],
            ],
            'business' => Cred::PROD_BUSINESS_ID->value,
            "customerReference" => $payload['customerReference'],
            "description" => $payload['description'],
            "destinationCurrency" => $payload['destinationCurrency'],
            "paymentDestination" => $payload['paymentDestination'],
            "sourceCurrency" => $payload['sourceCurrency'],
            "sender" => [
                "name" => $payload['sender']['name'],
                "email" => $payload['sender']['email'],
            ]
        ];

        try {
            $response = $this->httpClient->post('/disbursements/payouts', [
                'headers' => $this->buildAuthHeader(),
                'json' => $requiredPayload,
            ]);
            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (AppException $e) {
            throw new AppException("Failed to create DVA: " . $e->getMessage());
        }
    }

    private function performNGNTransferToCorporateAccount(array $payload): mixed
    {
        $requiredPayload = [
            'business' => Cred::PROD_BUSINESS_ID->value,
            "sourceCurrency" => $payload['sourceCurrency'],
            "destinationCurrency" => $payload['destinationCurrency'],
            "amount" => $payload['amount'],
            "description" => $payload['description'],
            "paymentDestination" => $payload['paymentDestination'],
            "customerReference" => $payload['customerReference'],
            "quoteReference" => $payload['quoteReference'],
            "beneficiary" => [
                "firstName" => $payload['beneficiary']['firstName'],
                "lastName" => $payload['beneficiary']['lastName'],
                "accountHolderName" => $payload['beneficiary']['accountHolderName'],
                "country" => $payload['beneficiary']['country'],
                "phone" => $payload['beneficiary']['phone'],
                "accountNumber" => $payload['beneficiary']['accountNumber'],
                "type" => $payload['beneficiary']['type'],
                "email" => $payload['beneficiary']['email'],
                "bankCode" => $payload['beneficiary']['bankCode'],
            ]
        ];

        try {
            $response = $this->httpClient->post('/disbursements/payouts', [
                'headers' => $this->buildAuthHeader(),
                'json' => $requiredPayload,
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

            $response =
                $this->httpClient->post('/profile/virtual-accounts/requests', [
                    'headers' => $this->buildAuthHeader(),
                    'json' => $payload,
                ]);
            $data =
                json_decode($response->getBody(), true);
            return $data;
        } catch (AppException $e) {
            throw new AppException("Failed to create DVA: " . $e->getMessage());
        }
    }

    // Verify a transfer using Fincra's API
    /**
     * Summary of verifyTransfer
     * @param string $reference
     * @throws \App\Exceptions\AppException
     * @return mixed
     */
    public function verifyTransfer(string $reference): mixed
    {
        try {
            $response = $this->httpClient->get("/disbursements/payouts/customer-reference/$reference", [
                'headers' => $this->buildAuthHeader(),
            ]);

            $statusCode = $response->getStatusCode();
            $data = json_decode($response->getBody(), true);

            switch ($statusCode) {
                case 200:
                    return $data;
                case 201:
                    return ['message' => 'Transfer created successfully', 'data' => $data];
                case 404:
                    throw new AppException("Transfer not found", 404);
                default:
                    throw new AppException("Failed to verify transfer: " . $response->getReasonPhrase(), $statusCode);
            }
        } catch (AppException $e) {
            throw new AppException($e->getMessage());
        }
    }
}