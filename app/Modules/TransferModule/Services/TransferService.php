<?php

namespace App\Modules\TransferModule\Services;

use App\Common\Enums\Cred;
use App\Common\Enums\ServiceProvider;
use App\Common\Enums\TransferType;
use App\Common\Helpers\CodeHelper;
use App\Common\Helpers\ResponseHelper;
use App\Exceptions\AppException;
use App\Models\Account;
use App\Models\TransactionEntry;
use App\Models\User;
use App\Modules\FincraModule\Services\FincraService;
use App\Modules\PaystackModule\Services\PaystackService;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Validator;

class TransferService
{
    public FincraService $fincraService;
    public PaystackService $paystackService;

    public TransactionService $transactionService;

    public function __construct()
    {
        $this->fincraService = FincraService::getInstance();
        $this->paystackService = PaystackService::getInstance();
        $this->transactionService = new TransactionService();
    }

    public function transfer(Request $request, string $account_id)
    {

        $validator = Validator::make($request->all(), [
            "transfer_type" => "required|string",
            "amount" => "required|integer",

            /// Whale to Whale
            "recieving_account_id" => "sometimes|string|required_if:transfer_type,corporate",
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                message: "Validation failed",
                error: $validator->errors()->toArray()
            );
        }
        DB::beginTransaction();

        $user = auth()->user();
        $account = $this->performSecurityCheckOnSender($account_id, $request->recieving_account_id, $request->amount);

        try {
            $accountType = ServiceProvider::tryFrom($account->service_provider);
            $transferType = TransferType::tryFrom($request['transfer_type']);

        } catch (AppException $e) {
            throw new AppException("Invalid Account Type or Invalid Transfer Type");
        }

        $lock = Cache::lock('transfer_lock_' . $user->id, 10);

        $response = [];
        try {
            if ($lock->get()) {
                if ($transferType === TransferType::WHALE_TO_WHALE) {
                    $response = $this->handleInternalTransfer($request, $account_id);
                } else {
                    switch ($accountType) {
                        case ServiceProvider::FINCRA:
                            $response = $this->handleFincraTransfer($request, $account_id);
                            break;
                        case ServiceProvider::PAYSTACK:
                            $response = $this->handlePaystackTransfer($request, $account_id);
                            break;
                        default:
                            return ResponseHelper::unprocessableEntity("Invalid account service provider.");
                    }
                }
            } else {
                throw new AppException('Too many requests, please try again.');
            }

            $transaction = $this->transactionService->registerTransaction($response,$transferType);

            return ResponseHelper::success(message: "Transfer Successful", data: $transaction);

        } catch (Exception $e) {
            DB::rollback();
            throw new AppException($e->getMessage());
        } finally {
            DB::commit();
            $lock->release();
        }
    }

    private function handleFincraTransfer(Request $request, string $account_id): array
    {
        $recieving_account_id = $request->get("recieving_account_id");
        $amount = $request->get("amount");
        $type = $request->get("type");
        $note = $request->get("note");
        $sender = $this->performSecurityCheckOnSender($account_id, $recieving_account_id, $amount);
        $user = auth()->user();
        $reference = CodeHelper::generateSecureReference();
        $payload = [
            'amount' => (int) $amount,
            'beneficiary' => [
                'accountHolderName' => $request->get("beneficiary_account_holder_name"),
                'accountNumber' => $request->get("beneficiary_account_number"),
                'bankCode' => $request->get("beneficiary_bank_code"),
                'firstName' => $request->get("beneficiary_first_name"),
                'lastName' => $request->get("beneficiary_last_name"),
                'type' => $request->get("beneficiary_type"),
                'country' => "NG",
                'phone' => $request->get("beneficiary_phone"),
                'email' => $request->get("beneficiary_email"),
            ],
            'business' => Cred::BUSINESS_ID->value,
            'customerReference' => $reference,
            'description' => $request->get("note"),
            'destinationCurrency' => "NGN",
            'paymentDestination' => 'bank_account',
            'sourceCurrency' => "NGN",
            'sender' => $user->profile_type == 'personal' ? [
                'name' => ($user->middle_name) ?
                    $user->first_name . " " . $user->last_name
                    : $user->first_name . " " . $user->middle_name . " " . $user->last_name,
                'email' => $sender->email,
            ] : [
                'name' => $user->business_name,
                'email' => $sender->email,
            ],
            // 'quoteReference' => CodeHelper::generate(20),
        ];

        $fincra_res = $this->fincraService->runTransfer(TransferType::BANK_ACCOUNT_TRANSFER, $payload);


        $response = [
            'currency' => $sender->currency,
            'to_sys_account_id' => null,
            'to_user_name' => $payload['beneficiary']['accountHolderName'],
            'to_user_email' => $payload['beneficiary']['email'],
            'to_bank_name' => request()->beneficiary_bank,
            'to_bank_code' => $payload['beneficiary']['bankCode'],
            'to_account_number' => $payload['beneficiary']['accountNumber'],
            'transaction_reference' => $reference,
            'status' => $fincra_res['data']['status'],
            'type' => $type,
            'amount' => $amount,
            'note' => "NIP/Transfer Digitwhale Innovations Limited - " . $note,
            'entry_type' => 'debit',
        ];


        return $response;
    }

    private function handlePaystackTransfer(Request $request, string $account_id): mixed
    {
        throw new AppException("Service Unavailable. Contact support to switch service provider.");
    }

    /// Whale to Whale Transfers
    private function handleInternalTransfer(Request $request, string $account_id)
    {
        try {
            $recieving_account_id = $request->get("recieving_account_id");
            $type = $request->get("type");
            $note = $request->get("note");
            $amount = $request->get("amount");
            $recieving_account = $this->performSecurityCheckOnReciever($recieving_account_id);
            $sender = $this->performSecurityCheckOnSender($account_id, $recieving_account_id, $amount);

            if ($recieving_account->currency != $sender->currency) {
                throw new AppException("Invalid Currency Exchange");
            }

            $newRecieverBalance = (int) $recieving_account->balance + (int) $amount;

            // credit reciever
            $recieving_account->update([
                'balance' => $newRecieverBalance,
            ]);

            $recieveing_user = User::where('id', $recieving_account->user_id)->first();

            $response = [
                'currency' => $sender->currency,
                'to_sys_account_id' => $recieving_account->id,
                'to_user_name' => $recieveing_user->profile_type != 'personal' ? $recieveing_user->business_name : $recieveing_user->first_name . "" . $recieveing_user->middle_name . " " . $recieveing_user->last_name,
                'to_user_email' => $recieveing_user->email,
                'to_bank_name' => $recieving_account->service_bank,
                'to_bank_code' => 'Internal Transfer',
                'to_account_number' => $recieving_account->account_number,
                'transaction_reference' => CodeHelper::generateSecureReference(),
                'status' => 'successful',
                'type' => $type,
                'amount' => $amount,
                'note' => "NIP/Transfer Digitwhale Innovations Limited - " .$note,
                'entry_type' => 'debit',
            ];

            return $response;

        } catch (Exception $e) {
            throw new AppException($e->getMessage());

        }

    }

    private function performSecurityCheckOnSender($account_id, $reciever_account_id, $amount): Account
    {

        $user = auth()->user();

        $account = Account::where("user_id", $user->id)
            ->where("account_id", $account_id)
            ->first();

        if (!$account) {
            throw new AppException("Invalid account id or account not found.");
        }

        if ($account->pnd) {
            throw new AppException('Account cannot transact at the moment. Contact customer support.');
        }

        /// checks if the amount send today is equal to the the daily sendable limit
        if ($account->daily_transaction_count == $account->daily_transaction_limit) {
            throw new AppException('Daily Transaction limit exceeded. Try again later.');
        }

        if ($account->blacklisted) {
            throw new AppException('[Sender Account Blacklisted] - Account cannot transacts at the moment. Contact customer support.');
        }

        if ($reciever_account_id == $account->id) {
            $account->update([
                'blacklisted' => true,
                'blacklist_text' => 'Self transfer perfomed [Threat Level 4/5].'
            ]);
            throw new AppException('Self transfer is not allowed.');
        }

        if ($amount >= $account->balance) {
            throw new AppException('Issuficient Funds.');
        }

        if ($amount <= 0) {
            throw new AppException('Invalid Amount.');
        }


        return $account;

    }

    private function performSecurityCheckOnReciever($recieving_account_id): Account
    {

        $user = auth()->user();

        $recieving_account = Account::where('account_id', $recieving_account_id)->first();
        if (!$recieving_account) {
            throw new AppException('Recieving Account Not found');
        }

        if (!$recieving_account) {
            throw new AppException("Invalid account id or account not found.");
        }

        if ($recieving_account->pnc) {
            throw new AppException('Account cannot recieve funds at the moment. Contact customer support.');
        }

        return $recieving_account;

    }

    public function verifyTransferStatusBy()
    {
        try {
            $reference = request()->input('reference');
            $response = $this->fincraService->verifyTransfer($reference);
            $transaction_entry = TransactionEntry::where('transaction_reference', $reference)->first();

            $transaction_entry->update([
                'status' => $response['data']['status'],
            ]);

            $transaction_entry = TransactionEntry::where('transaction_reference', $reference)->first();

            return ResponseHelper::success($transaction_entry);
        } catch (Exception $e) {
            return ResponseHelper::error(error: $e->getMessage());
        }
    }
}