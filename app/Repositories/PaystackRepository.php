<?php

namespace App\Repositories;

use App\Dtos\PaystackDtos\BankDto\Bank;
use App\Dtos\PaystackDtos\BankDto\BankResponse;
use App\Dtos\PaystackDtos\CountryDto\Country;
use App\Dtos\PaystackDtos\CountryDto\CountryResponse;
use App\Dtos\PaystackDtos\CreateAndStoreRecipientResponse;
use App\Dtos\PaystackDtos\StateDto\State;
use App\Dtos\PaystackDtos\StateDto\StateResponse;
use App\Models\PaystackCustomer;
use App\Models\PaystackDVA;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Common\Helpers\CodeHelper;

class PaystackRepository
{
    protected $description = "Paystack Repository - (Contains methods for payments, transaction verification, customer management)";

    protected string $baseUrl = "https://api.paystack.co/";

    protected array $headers;

    protected string $secretKey;

    public function __construct()
    {
        $this->secretKey = env("PAYSTACK_TEST_SK", "");
        $this->headers = [
            "Authorization" => "Bearer " . $this->secretKey,
            "Content-Type" => "application/json",
            "Cache-Control: no-cache",
        ];
    }

    /**
     * Create a new Paystack customer using the given User data and save the customer details to the database.
     *
     * @param User $user
     * @return array|null The response data from Paystack or null if the request fails.
     */
    public function createAndSaveCustomer(User $user): ?PaystackCustomer
    {
        $endpoint = "customer";
        $url = $this->baseUrl . $endpoint;

        $data = [
            "email" => $user->email,
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
            "phone" => $user->phone,
        ];

        try {
            $response = Http::withHeaders($this->headers)->post($url, $data);

            if ($response->successful()) {
                $responseData = $response->json();

                // Check if the response has the correct structure
                if (
                    isset($responseData["status"]) &&
                    $responseData["status"] === true &&
                    isset($responseData["data"])
                ) {
                    $customerData = $responseData["data"];

                    // Ensure all necessary fields are present
                    $paystackCustomer = PaystackCustomer::updateOrCreate(
                        ["paystack_id" => $customerData["id"]],
                        [
                            "email" => $customerData["email"],
                            "integration" => $customerData["integration"],
                            "domain" => $customerData["domain"],
                            "customer_code" => $customerData["customer_code"],
                            "identified" => $customerData["identified"],
                            "identifications" =>
                                $customerData["identifications"],
                            "created_at" => $customerData["createdAt"],
                            "updated_at" => $customerData["updatedAt"],
                        ]
                    );

                    Log::info("Paystack customer creation done", [
                        "response" => $responseData,
                        "paystackCustomer" => $paystackCustomer,
                    ]);

                    return $paystackCustomer;
                } else {
                    Log::error(
                        "Paystack response has an unexpected structure",
                        [
                            "response" => $responseData,
                        ]
                    );
                }
            } else {
                Log::error("Paystack customer creation failed", [
                    "response" => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Exception occurred while creating Paystack customer", [
                "exception" => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Creates and stores a new recipient in Paystack.
     *
     * If the account number already exists in the Paystack system, the existing record will be retrieved.
     * This is used when transferring to an account that was not created within the system (i.e., an external bank account).
     *
     * @param User $user The user object containing account information.
     * @param string $bank_code The bank code for the recipient's bank.
     *
     * @return CreateAndStoreRecipientResponse The response from Paystack's recipient creation endpoint.
     */
    public function createAndStoreRecipient(
        User $user,
        string $bank_code
    ): CreateAndStoreRecipientResponse {
        $endpoint = "/transferrecipient";
        $url = $this->baseUrl . $endpoint;

        $data = [
            "type" => "nuban",
            "name" => $user->fullName(),
            "account_number" => $user->account()->account_number,
            "bank_code" => $bank_code,
            "currency" => "NGN",
        ];
        $response = Http::withHeaders($this->headers)->post($url, $data);

        $req_response = new CreateAndStoreRecipientResponse(
            status: false,
            message: "Failed to create Recipient",
            data: []
        );

        $responseData = $response->json();
        if ($responseData["status"]) {
            $req_response->status = true;
            $req_response->message = $responseData["message"];

            $req_response->data = [
                "active" => $responseData["data"]["active"],
                "createdAt" => $responseData["data"]["createdAt"],
                "currency" => $responseData["data"]["currency"],
                "domain" => $responseData["data"]["domain"],
                "id" => $responseData["data"]["id"],
                "integration" => $responseData["data"]["integration"],
                "name" => $responseData["data"]["name"],
                "recipient_code" => $responseData["data"]["recipient_code"],
                "type" => $responseData["data"]["type"],
                "updatedAt" => $responseData["data"]["updatedAt"],
                "is_deleted" => $responseData["data"]["is_deleted"],
                "details" => $responseData["data"]["details"],
            ];
        } else {
            $req_response->status = false;
            $req_response->message =
                $responseData["message"] ?? "Failed to create Recipient";
            $req_response->data = [];
        }

        return $req_response;
    }

    /**
     * Initiates a transfer to a recipient in Paystack.
     *
     * This function sends a request to Paystack to transfer funds to a recipient.
     * The response will indicate whether the transfer requires an OTP to continue or if it was successful.
     *
     * @param string $recipient_code The Paystack recipient code to which the funds will be sent.
     * @param int $amount The amount to be transferred in kobo (e.g., 3794800 for 37,948 NGN).
     * @param string $reason The reason for the transfer.
     *
     * @return array The response from Paystack's transfer initiation endpoint.
     */
    public function initiateTransfer(
        string $recipient_code,
        int $amount,
        string $reason
    ): array {
        $endpoint = "/transfer";
        $url = $this->baseUrl . $endpoint;

        $data = [
            "source" => "balance",
            "recipient" => $recipient_code,
            "amount" => $amount,
            "reason" => $reason,
        ];

        $response = Http::withHeaders($this->headers)->post($url, $data);

        $responseData = $response->json();

        $transferResponse = [
            "status" => false,
            "message" => "Failed to initiate transfer",
            "data" => [],
        ];

        if ($responseData["status"]) {
            $transferResponse["status"] = true;
            $transferResponse["message"] = $responseData["message"];

            $transferResponse["data"] = [
                "integration" => $responseData["data"]["integration"],
                "domain" => $responseData["data"]["domain"],
                "amount" => $responseData["data"]["amount"],
                "currency" => $responseData["data"]["currency"],
                "source" => $responseData["data"]["source"],
                "reason" => $responseData["data"]["reason"],
                "recipient" => $responseData["data"]["recipient"],
                "status" => $responseData["data"]["status"],
                "transfer_code" => $responseData["data"]["transfer_code"],
                "id" => $responseData["data"]["id"],
                "createdAt" => $responseData["data"]["createdAt"],
                "updatedAt" => $responseData["data"]["updatedAt"],
            ];
        } else {
            $transferResponse["message"] =
                $responseData["message"] ?? "Failed to initiate transfer";
        }

        return $transferResponse;
    }

    /**
     * Resolves a bank account using Paystack.
     *
     * This function sends a request to Paystack to resolve an account number and retrieve the account holder's name.
     *
     * @param string $account_number The account number to be resolved.
     * @param string $bank_code The bank code of the bank where the account is held.
     *
     * @return array The response from Paystack's account resolution endpoint.
     */
    public function resolveAccount(
        string $account_number,
        string $bank_code
    ): array {
        $endpoint = "/bank/resolve";
        $url =
            $this->baseUrl .
            $endpoint .
            "?account_number=" .
            $account_number .
            "&bank_code=" .
            $bank_code;

        $response = Http::withHeaders($this->headers)->get($url);

        $responseData = $response->json();

        $resolveResponse = [
            "status" => false,
            "message" => "Failed to resolve account",
            "data" => [],
        ];

        if ($responseData["status"]) {
            $resolveResponse["status"] = true;
            $resolveResponse["message"] = $responseData["message"];

            $resolveResponse["data"] = [
                "account_number" => $responseData["data"]["account_number"],
                "account_name" => $responseData["data"]["account_name"],
            ];
        } else {
            $resolveResponse["message"] =
                $responseData["message"] ?? "Failed to resolve account";
        }

        return $resolveResponse;
    }

    public function getBanks(): BankResponse
    {
        $endpoint = "/bank";
        $url = $this->baseUrl . $endpoint;

        $response = Http::withHeaders($this->headers)->get($url);

        $responseData = $response->json();

        $banksResponse = new BankResponse();
        $banksResponse->status = $responseData["status"];
        $banksResponse->message = $responseData["message"];

        $banksResponse->data = array_map(function ($item) {
            $bank = new Bank();
            $bank->name = $item["name"];
            $bank->slug = $item["slug"];
            $bank->code = $item["code"];
            $bank->longcode = $item["longcode"];
            $bank->gateway = $item["gateway"];
            $bank->pay_with_bank = $item["pay_with_bank"];
            $bank->active = $item["active"];
            $bank->is_deleted = $item["is_deleted"];
            $bank->country = $item["country"];
            $bank->currency = $item["currency"];
            $bank->type = $item["type"];
            $bank->id = $item["id"];
            $bank->createdAt = $item["createdAt"];
            $bank->updatedAt = $item["updatedAt"];
            return $bank;
        }, $responseData["data"]);

        $banksResponse->meta = $responseData["meta"];

        return $banksResponse;
    }

    public function getCountries(): CountryResponse
    {
        $endpoint = "/country";
        $url = $this->baseUrl . $endpoint;

        $response = Http::withHeaders($this->headers)->get($url);

        $responseData = $response->json();

        $countriesResponse = new CountryResponse();
        $countriesResponse->status = $responseData["status"];
        $countriesResponse->message = $responseData["message"];

        $countriesResponse->data = array_map(function ($item) {
            $country = new Country();
            $country->id = $item["id"];
            $country->name = $item["name"];
            $country->iso_code = $item["iso_code"];
            $country->default_currency_code = $item["default_currency_code"];
            $country->integration_defaults = $item["integration_defaults"];
            $country->relationships = $item["relationships"];
            return $country;
        }, $responseData["data"]);

        return $countriesResponse;
    }

    public function getStates(string $countryCode): StateResponse
    {
        $endpoint = "/address_verification/states";
        $url = $this->baseUrl . $endpoint . "?country=" . $countryCode;

        $response = Http::withHeaders($this->headers)->get($url);

        $responseData = $response->json();

        $statesResponse = new StateResponse();
        $statesResponse->status = $responseData["status"];
        $statesResponse->message = $responseData["message"];

        $statesResponse->data = array_map(function ($item) {
            $state = new State();
            $state->name = $item["name"];
            $state->slug = $item["slug"];
            $state->abbreviation = $item["abbreviation"];
            return $state;
        }, $responseData["data"]);

        return $statesResponse;
    }

    public function generateDVA(string $customerCode): ?PaystackDVA
    {
        $endpoint = "dedicated_account";
        $url = $this->baseUrl . $endpoint;

        $requestBody = [
            "customer" => $customerCode,
            "preferred_bank" => "Wema Bank",
        ];

        try {
            $response = Http::withHeaders($this->headers)->post(
                $url,
                $requestBody
            );
            $responseData = $response->json();

            // Check if the response was successful
            if (!$responseData["status"]) {
                // Log the failure and rollback the transaction
                Log::error("DVA creation failed", [
                    "response" => $responseData,
                ]);
                DB::rollBack();
                return null;
            }

            // Map the response data to the PaystackDVA model
            $paystackDVA = PaystackDVA::create([
                "bank_name" => $responseData["data"]["bank"]["name"],
                "bank_id" => $responseData["data"]["bank"]["id"],
                "bank_slug" => $responseData["data"]["bank"]["slug"],
                "account_name" => $responseData["data"]["account_name"],
                "account_number" => $responseData["data"]["account_number"],
                "assigned" => $responseData["data"]["assigned"],
                "currency" => $responseData["data"]["currency"],
                "active" => $responseData["data"]["active"],
                "dva_id" => $responseData["data"]["id"],
                "integration" => $responseData["data"]["integration"],
                "assignee_id" => $responseData["data"]["assignee_id"],
                "assignee_type" => $responseData["data"]["assignee_type"],
                "expired" => $responseData["data"]["expired"],
                "account_type" => $responseData["data"]["account_type"],
                "assigned_at" => $responseData["data"]["assigned_at"],
                "customer_id" => $responseData["data"]["customer"]["id"],
                "customer_first_name" =>
                    $responseData["data"]["customer"]["first_name"],
                "customer_last_name" =>
                    $responseData["data"]["customer"]["last_name"],
                "customer_email" => $responseData["data"]["customer"]["email"],
                "customer_code" =>
                    $responseData["data"]["customer"]["customer_code"],
                "customer_phone" => $responseData["data"]["customer"]["phone"],
                "customer_risk_action" =>
                    $responseData["data"]["customer"]["risk_action"],
            ]);

            return $paystackDVA;
        } catch (Exception $e) {
            Log::error("Error generating DVA", ["error" => $e->getMessage()]);
            return null;
        }
    }

    public function testGenerateDVA(
        User $user,
        string $customerCode
    ): PaystackDVA {
        // Define static data for testing
        $responseData = [
            "status" => true,
            "message" => "NUBAN successfully created",
            "data" => [
                "bank" => [
                    "name" => "Titan Paystack",
                    "id" => 1,
                    "slug" => "titan-paystack",
                ],
                "account_name" => $user->getFullName(),
                "account_number" => User::generateUniqueBankAccountNumber(),
                "assigned" => true,
                "currency" => "NGN",
                "metadata" => null,
                "active" => true,
                "id" => CodeHelper::generate(3, true),
                "created_at" => "2019-12-12T12:39:04.000Z",
                "updated_at" => "2020-01-06T15:51:24.000Z",
                "assignment" => [
                    "integration" => CodeHelper::generate(6, true),
                    "assignee_id" => CodeHelper::generate(7, true),
                    "assignee_type" => "Customer",
                    "expired" => false,
                    "account_type" => "PAY-WITH-TRANSFER-RECURRING",
                    "assigned_at" => "2020-01-06T15:51:24.764Z",
                ],
                "customer" => [
                    "id" => CodeHelper::generate(7, true),
                    "first_name" => $user->first_name,
                    "last_name" => $user->last_name,
                    "email" => $user->email,
                    "customer_code" => $customerCode,
                    "phone" => "+1234567890",
                    "risk_action" => "default",
                ],
            ],
        ];

        // Map the response data to the PaystackDVA model
        $paystackDVA = PaystackDVA::create([
            "bank_name" => $responseData["data"]["bank"]["name"],
            "bank_id" => $responseData["data"]["bank"]["id"],
            "bank_slug" => $responseData["data"]["bank"]["slug"],
            "account_name" => $responseData["data"]["account_name"],
            "account_number" => $responseData["data"]["account_number"],
            "assigned" => $responseData["data"]["assigned"],
            "currency" => $responseData["data"]["currency"],
            "active" => $responseData["data"]["active"],
            "dva_id" => $responseData["data"]["id"],
            "integration" => $responseData["data"]["assignment"]["integration"],
            "assignee_id" => $responseData["data"]["assignment"]["assignee_id"],
            "assignee_type" =>
                $responseData["data"]["assignment"]["assignee_type"],
            "expired" => $responseData["data"]["assignment"]["expired"],
            "account_type" =>
                $responseData["data"]["assignment"]["account_type"],
            "assigned_at" => $responseData["data"]["assignment"]["assigned_at"],
            "customer_id" => $responseData["data"]["customer"]["id"],
            "customer_first_name" =>
                $responseData["data"]["customer"]["first_name"],
            "customer_last_name" =>
                $responseData["data"]["customer"]["last_name"],
            "customer_email" => $responseData["data"]["customer"]["email"],
            "customer_code" =>
                $responseData["data"]["customer"]["customer_code"],
            "customer_phone" => $responseData["data"]["customer"]["phone"],
            "customer_risk_action" =>
                $responseData["data"]["customer"]["risk_action"],
        ]);

        return $paystackDVA;
    }
}
