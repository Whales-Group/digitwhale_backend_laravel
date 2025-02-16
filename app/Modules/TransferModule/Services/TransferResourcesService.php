<?php

namespace App\Modules\TransferModule\Services;

use App\Common\Enums\IdentifierType;
use App\Common\Enums\ServiceProvider;
use App\Common\Helpers\CodeHelper;
use App\Common\Helpers\ResponseHelper;
use App\Exceptions\AppException;
use App\Models\Account;
use App\Models\TransactionEntry;
use App\Modules\FincraModule\Services\FincraService;
use App\Modules\PaystackModule\Services\PaystackService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferResourcesService
{
    public FincraService $fincraService;
    public PaystackService $paystackService;

    public function __construct()
    {
        $this->fincraService = FincraService::getInstance();
        $this->paystackService = PaystackService::getInstance();
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
                return ResponseHelper::notFound("Tranaction not found. Access is Invalid");
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

}
