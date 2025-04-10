<?php

namespace App\Modules\TransferModule\Services;

use App\Enums\ErrorCode;
use App\Enums\IdentifierType;
use App\Enums\ServiceProvider;
use App\Enums\TransferType;
use App\Exceptions\AppException;
use App\Exceptions\CodedException;
use App\Helpers\CodeHelper;
use App\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\Beneficiary;
use App\Models\TransactionEntry;
use App\Gateways\Fincra\Services\FincraService;
use App\Gateways\FlutterWave\Services\FlutterWaveService;
use App\Gateways\Paystack\Services\PaystackService;
use Exception;
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

    public function getBanks(Request $request, string $account_id)
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
    public function resolveAccountNumber(Request $request, string $account_id)
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

                    return ResponseHelper::success($response);
                case ServiceProvider::PAYSTACK:
                    $paystack_res = $this->paystackService->resolveAccount($account_number, $bank_code);
                    $response['accountName'] = trim($paystack_res['data']['account_name']);
                    $response['accountNumber'] = trim($paystack_res['data']['account_number']);

                    return ResponseHelper::success($response);
                case ServiceProvider::FLUTTERWAVE:

                    $flutter_wave_res = $this->handleBeneficiaryCreation($request);
                    $response['accountName'] = trim($flutter_wave_res['data']['name']);
                    $response['accountNumber'] = trim($flutter_wave_res['data']['account_number']);

                    return ResponseHelper::success($response);
                default:
                    $response['message'] = "failed to verify account: check and try again.";
                    return ResponseHelper::success($response);

            }

        } catch (AppException $e) {

            return ResponseHelper::unprocessableEntity($e->getMessage());
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
    public function verifyTransferStatusBy($account_id)
    {
        try {
            $user = auth()->user();
            $reference = request()->input('reference');

            if (!$account_id || !$reference) {
                throw new AppException("Account Id and Reference are required");
            }

            $account = Account::where("user_id", $user->id)->where("account_id", $account_id)->first();

            if (!$account) {
                return ResponseHelper::notFound("Account not found. Access is Invalid");
            }

            $accountType = ServiceProvider::tryFrom($account->service_provider) ?? throw new AppException("Invalid Account Type");

            $transaction_entry = TransactionEntry::where('transaction_reference', $reference)->first();
            $status = $transaction_entry->status ?? 'pending';

            if ($transaction_entry->to_sys_account_id && $transaction_entry->from_sys_account_id) {
                $status = $transaction_entry->status ?? $status;
            } else {
                $status = match ($accountType) {
                    ServiceProvider::FINCRA => $this->fincraService->verifyTransfer($reference)['data']['status'],
                    ServiceProvider::PAYSTACK => $this->paystackService->verifyTransfer($reference)['data']['status'],
                    default => throw new AppException("Invalid account service provider."),
                };
            }

            $transaction_entry->update(['status' => $status]);

            return ResponseHelper::success($transaction_entry, "Transaction status verification successful.");
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }


    /**
     * Validates a transfer request, calculates charges, and generates a validation code.
     *
     * @return JsonResponse
     * @throws AppException
     */
    public function validateTransfer(?Request $request = null): JsonResponse
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

        $accountType = ServiceProvider::tryFrom($account->service_provider)
            ?? throw new AppException("Invalid Service Provider");

        // Calculate transfer fee based on provider
        $transferFee = match ($accountType) {
            ServiceProvider::FINCRA => 50,
            ServiceProvider::PAYSTACK => 10,
            ServiceProvider::FLUTTERWAVE => $data['amount'] <= 5000 ? 10 : ($data['amount'] <= 50000 ? 25 : 50),
            default => throw new AppException("Invalid account service provider."),
        };

        $availableBalance = match ($accountType) {
            ServiceProvider::FINCRA => $this->fincraService->getWalletBalance()["availableBalance"],
            ServiceProvider::PAYSTACK => collect($this->paystackService->getWalletBalance())->firstWhere('currency', 'NGN')['balance'] / 100,
            ServiceProvider::FLUTTERWAVE => 0, // TODO: IMPL FLUTTERWAVE BALANCE CHECK
            default => throw new AppException("Invalid account service provider."),
        };

        if ($transferType != TransferType::WHALE_TO_WHALE && (float) $availableBalance - (float) $data['amount'] < 150) {
            throw new CodedException(ErrorCode::INSUFFICIENT_PROVIDER_BALANCE);
        }

        $token = CodeHelper::generate(10);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => $token,
            'created_at' => now(),
        ]);
        $validationCode = $token;

        $response = [
            'charge' => $transferType === TransferType::BANK_ACCOUNT_TRANSFER ? $transferFee : 0,
            'transfer_type' => $transferType,
            'code' => $validationCode,
            'message' => match ($transferType) {
                TransferType::BANK_ACCOUNT_TRANSFER => 'Bank Account transfer validation successful.',
                TransferType::WHALE_TO_WHALE => 'Whale to Whale transfer validation successful.',
                TransferType::CROSS_CURRENCY_PAYOUT => 'Cross Currency Payout transfer validation successful.',
                default => throw new AppException("Invalid transfer type."),
            },
        ];

        return ResponseHelper::success($response);
    }
    private function handleBeneficiaryCreation(Request $request): Beneficiary
    {
        $existingBeneficiary = Beneficiary::where('user_id', auth()->id())
            ->where('account_number', $request->beneficiary_account_number)
            ->first();

        if ($existingBeneficiary) {
            return $existingBeneficiary;
        }

        $this->flutterWaveService->createTransferRecipient([
            "account_bank" => $request->bank_code,
            "account_number" => $request->account_number,
            "beneficiary_name" => "New Benefitiary",
            "currency" => "NGN",
            "bank_name" => "New Benefitiary Bank",
        ]);

        return Beneficiary::create([
            'user_id' => auth()->id(),
            'name' => $request->beneficiary_account_holder_name,
            'type' => 'cash_transfer',
            'account_number' => $request->beneficiary_account_number,
            'bank_name' => $request->beneficiary_bank_name,
            'bank_code' => $request->beneficiary_bank_code,
            'is_favorite' => false,
        ]);
    }


}
