<?php

namespace App\Modules\TransferModule\Services;

use App\Enums\ErrorCode;
use App\Enums\IdentifierType;
use App\Enums\ServiceProvider;
use App\Enums\TransferType;
use App\Exceptions\AppException;
use App\Exceptions\CodedException;
use App\Gateways\Fincra\Services\FincraService;
use App\Gateways\FlutterWave\Services\FlutterWaveService;
use App\Gateways\Paystack\Services\PaystackService;
use App\Helpers\CodeHelper;
use App\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\AppLog;
use App\Models\Beneficiary;
use App\Models\TransactionEntry;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferResourcesService
{
    public FincraService $fincraService;
    public PaystackService $paystackService;
    public FlutterWaveService $flutterWaveService;

    public function __construct()
    {
        $this->fincraService = FincraService::getInstance();
        $this->paystackService = PaystackService::getInstance();
        $this->flutterWaveService = FlutterWaveService::getInstance();

    }

    public function getBanks(Request $request, string $account_id): JsonResponse
    {
        try {
            $user = auth()->user();
            $account = Account::where("user_id", $user->id)->where("account_id", $account_id)->first();

            if (!$account) {
                throw new AppException("Invalid account id or account not found.");
            }

            try {
                $accountType = ServiceProvider::tryFrom($account->service_provider);
            } catch (AppException $e) {
                throw new AppException("Invalid Account Type");
            }

            switch ($accountType) {
                case ServiceProvider::FINCRA:
                    return ResponseHelper::success($this->fincraService->getBanks()['data']);
                case ServiceProvider::PAYSTACK:
                    return ResponseHelper::success($this->paystackService->getBanks()['data']);
                case ServiceProvider::FLUTTERWAVE;
                    return ResponseHelper::success($this->flutterWaveService->getBanks()['data']);
                default:
                    return ResponseHelper::unprocessableEntity("Invalid account service provider.");
            }

        } catch (AppException $e) {
            return ResponseHelper::unprocessableEntity("Failed to get Banks");
        }
    }

    public function resolveAccountNumber(Request $request, string $account_id): JsonResponse
    {
        $bank_code = trim($request->input('bank_code'));
        $account_number = substr(trim($request->input('account_number')), 0, 10);
        try {
            $user = auth()->user();
            $account = Account::where("user_id", $user->id)->where("account_id", $account_id)->first();
            $response = ['accountName' => "", 'accountNumber' => ""];
            if (strlen($account_number) > 10) {
                throw new AppException("accountNumber length must be 10 characters long");
            }

            if (!$account) {
                throw new AppException("Invalid account id or account not found.");
            }

            try {
                $serviceProvider = ServiceProvider::tryFrom($account->service_provider);
            } catch (AppException $e) {
                throw new AppException("Invalid Account Type");
            }

            switch ($serviceProvider) {
                case ServiceProvider::FINCRA:
                    $fincra_res = $this->fincraService->resolveAccount($account_number, $bank_code);
                    $response['accountName'] = trim($fincra_res['data']['accountName']);
                    $response['accountNumber'] = trim($fincra_res['data']['accountNumber']);
                    break;

                case ServiceProvider::PAYSTACK:
                    $paystack_res = $this->paystackService->resolveAccount($account_number, $bank_code);
                    $response['accountName'] = trim($paystack_res['data']['account_name']);
                    $response['accountNumber'] = trim($paystack_res['data']['account_number']);
                    break;

                case ServiceProvider::FLUTTERWAVE:
                    $flutter_wave_res = $this->flutterWaveService->resolveAccount($account_number, $bank_code);
                    $response['accountName'] = trim($flutter_wave_res['data']['account_name']);
                    $response['accountNumber'] = trim($flutter_wave_res['data']['account_number']);
                    break;

                default:
                    $response['message'] = "failed to verify account: check and try again. bank_code: $bank_code, account_number: $account_number";

            }

            $this->createBeneficiary($response, $account);

            return ResponseHelper::success($response);

        } catch (AppException $e) {

            return ResponseHelper::unprocessableEntity($e->getMessage());
        } catch (GuzzleException $e) {

            return ResponseHelper::unprocessableEntity($e->getMessage());
        }
    }

    /**
     * @throws AppException
     */
    public function createBeneficiary(array $response, Account $account): void
    {
        try {
            $serviceProviderResponse = [
                "id" => 0,
                "bank_name" => ""
            ];

            $existingBeneficiary = Beneficiary::where('user_id', auth()->id())
                ->where('account_number', $response["accountNumber"])
                ->first();

            $existinBenAccount = Account::firstWhere('account_number', $response["accountNumber"]);

            if ($existinBenAccount) {
                $existingBeneficiary = Beneficiary::where('user_id', auth()->id())
                    ->where('account_id', $existinBenAccount->account_id)
                    ->first();
            }

            if ($existingBeneficiary) {
                return;
            }

            try {
                $serviceProvider = ServiceProvider::tryFrom($account->service_provider);
            } catch (AppException $e) {
                throw new AppException("Invalid Account Type");
            }

            switch ($serviceProvider) {
                case ServiceProvider::FINCRA:
                    throw new AppException("failed to verify account: check and try again.");

                case ServiceProvider::PAYSTACK:
                    throw new AppException("failed to verify account: check and try again.");

                case ServiceProvider::FLUTTERWAVE:
                    $res = $this->flutterWaveService->createTransferRecipient($response["accountNumber"], request()->bank_code);
                    $serviceProviderResponse = [
                        "id" => $res["data"]["id"],
                        "bank_name" => $res["data"]["bank_name"],
                    ];
                    break;

                default:
                    throw new AppException("failed to verify account: check and try again.");
            }

            Beneficiary::create([
                'user_id' => auth()->id(),
                'name' => $response["accountName"],
                'type' => 'cash_transfer',
                'account_number' => $response["accountNumber"],
                'account_id' => $account->account_id,
                'bank_name' => $serviceProviderResponse["bank_name"],
                'bank_code' => request()->bank_code,
                'is_favorite' => false,
                'unique_id' => $serviceProviderResponse["id"],
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }

    }

    public function resolveAccountByIdentity(Request $request): JsonResponse
    {
        try {
            $identity = $request->get("identity");
            $identityType = CodeHelper::getIdentifyType($identity);

            $account = match ($identityType) {
                IdentifierType::Email => Account::firstWhere('email', $identity),
                IdentifierType::Tag => Account::firstWhere('tag', $identity),
                IdentifierType::Phone => Account::firstWhere('phone_number', $identity),
                IdentifierType::AccountNumber => Account::firstWhere('account_number', $identity),
                default => throw new AppException("Invalid resolve identity."),
            };

            if (!$account) {
                throw new AppException("Account Not Found");
            }

            $response = [
                'accountName' => $account->validated_name,
                'accountNumber' => $account->account_number,
                'accountId' => $account->account_id,
                'identified_by' => $identityType
            ];

            return ResponseHelper::success($response);
        } catch (AppException $e) {
            return ResponseHelper::error($e->getMessage(), data: [
                'identified_by' => $identityType
            ]);
        } catch (Exception $e) {
            return ResponseHelper::unprocessableEntity("Unable to resolve account.");
        }
    }
    public function verifyTransferStatusBy($account_id): JsonResponse
    {
        try {
            $user = auth()->user();
            $reference = request()->input('reference');

            if (empty($account_id) || empty($reference)) {
                throw new AppException("Account ID and Reference are required.");
            }

            $account = Account::where('user_id', $user->id)
                ->where('account_id', $account_id)
                ->first();

            if (!$account) {
                return ResponseHelper::notFound("Account not found or access is invalid.");
            }

            $accountType = ServiceProvider::tryFrom($account->service_provider)
                ?? throw new AppException("Invalid Account Type.");

            $transactionEntry = TransactionEntry::where('transaction_reference', $reference)->first();

            if (!$transactionEntry) {
                return ResponseHelper::notFound('Transaction not found.');
            }

            $currentStatus = $transactionEntry->status ?? 'pending';

            if (empty($transactionEntry->to_sys_account_id) || empty($transactionEntry->from_sys_account_id)) {
                // External transaction: Need to verify with provider
                $currentStatus = match ($accountType) {
                    ServiceProvider::FINCRA => $this->fincraService->verifyTransfer($reference)['data']['status'],
                    ServiceProvider::PAYSTACK => $this->paystackService->verifyTransfer($reference)['data']['status'],
                    ServiceProvider::FLUTTERWAVE => $this->flutterWaveService->verifyTransfer($reference)['data']['status'],
                    default => throw new AppException("Unsupported service provider for transaction verification."),
                };
            }

            $transactionEntry->update(['status' => $currentStatus]);

            AppLog::info($transactionEntry);

            return ResponseHelper::success($transactionEntry, "Transaction status verification successful.");
        } catch (ClientException $e) {

            $responseBody = $e->getResponse()->getBody()->getContents();

            $errorData = json_decode($responseBody, true);

            if ($errorData['errorType'] === "RESOURCE_NOT_FOUND") {
                throw new AppException($errorData['message'] ?? "Transaction not found.");
            }

            DB::rollBack();
            throw $e;

        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        } finally {
            DB::commit();
        }
    }


    /**
     * Validates a transfer request, calculates charges, and generates a validation code.
     *
     * @return JsonResponse | array
     * @throws AppException
     */
    public function validateTransfer(?Request $request = null, bool $clearJson = false): JsonResponse|array
    {
        $user = auth()->user();

        $data = ($request ?? request())->validate([
            'transferType' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'account_id' => 'required|string',
        ]);

        $transferType = TransferType::tryFrom($data['transferType'])
            ?? throw new AppException("Invalid transfer type.");

        $account = Account::where('user_id', $user->id)
            ->where('account_id', $data['account_id'])
            ->first();

        if (!$account) {
            throw new AppException("Invalid account id or account not found.");
        }

        if ($data['amount'] < 100) {
            throw new AppException("Minimun transaction amount is 100.");
        }

        $accountType = ServiceProvider::tryFrom($account->service_provider)
            ?? throw new AppException("Invalid Service Provider");

        // Calculate transfer fee based on provider
        $transferFee = $transferType == TransferType::WHALE_TO_WHALE ? 0 : match ($accountType) {
            ServiceProvider::FINCRA => 50,
            ServiceProvider::PAYSTACK => 10,
            ServiceProvider::FLUTTERWAVE => $data['amount'] <= 5000 ? 10 : ($data['amount'] <= 50000 ? 25 : 50),
            default => throw new AppException("Invalid account service provider."),
        };

        ["availableBalance" => $availableBalance, "provider" => $provider] = match ($accountType) {
            ServiceProvider::FINCRA => [
                "availableBalance" => $this->fincraService->getWalletBalance()["availableBalance"],
                "provider" => ServiceProvider::FINCRA->value,
            ],
            ServiceProvider::PAYSTACK => [
                "availableBalance" => collect($this->paystackService->getWalletBalance())
                    ->firstWhere('currency', 'NGN')['balance'],
                "provider" => ServiceProvider::PAYSTACK->value,
            ],
            ServiceProvider::FLUTTERWAVE => [
                "availableBalance" => collect($this->flutterWaveService->getWalletBalance())
                    ->firstWhere('currency', 'NGN')['available_balance'],
                "provider" => ServiceProvider::FLUTTERWAVE->value,
            ],
            default => throw new AppException("Invalid account service provider."),
        };

        if ($transferType != TransferType::WHALE_TO_WHALE && (float) $availableBalance - (float) $data['amount'] < 10) {
            throw new CodedException(ErrorCode::INSUFFICIENT_PROVIDER_BALANCE);
        }


        $token = CodeHelper::generate(10);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => $token,
            'amount' => $data['amount'] + $transferFee,
            'charge' => $transferFee,
            'created_at' => now(),
        ]);
        $validationCode = $token;

        $response = [
            'charge' => $transferType === TransferType::BANK_ACCOUNT_TRANSFER ? $transferFee : 0,
            'transfer_type' => $transferType,
            'code' => $validationCode,
            'validated_amount' => $data['amount'] + $transferFee,
            'message' => match ($transferType) {
                TransferType::BANK_ACCOUNT_TRANSFER => 'Bank Account transfer validation successful.',
                TransferType::WHALE_TO_WHALE => 'Whale to Whale transfer validation successful.',
                TransferType::CROSS_CURRENCY_PAYOUT => 'Cross Currency Payout transfer validation successful.',
                default => throw new AppException("Invalid transfer type."),
            },
        ];

        if ($clearJson) {
            return $response;
        } else {
            return ResponseHelper::success($response);

        }
    }


}
