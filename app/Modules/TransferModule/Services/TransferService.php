<?php

namespace App\Modules\TransferModule\Services;

use App\Enums\ServiceProvider;
use App\Enums\TransferType;
use App\Exceptions\AppException;
use App\Helpers\CodeHelper;
use App\Helpers\ResponseHelper;
use App\Models\Account;
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
    public TransferResourcesService $transferResourse;
    public TransactionService $transactionService;
    public function __construct()
    {
        $this->fincraService = FincraService::getInstance();
        $this->paystackService = PaystackService::getInstance();
        $this->transactionService = new TransactionService();
        $this->transferResourse = new TransferResourcesService();
    }

    public function transfer(Request $request, string $account_id)
    {

        $validator = Validator::make($request->all(), [
            "code" => "required|string",
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

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->where('token', $request->code)
            ->first();

        if (!$tokenRecord) {
            return ResponseHelper::unprocessableEntity(
                message: "Invalidated Transfer.",
                error: [
                    "transfer_code" => ["The transfer is invalid."]
                ]
            );
        }

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->where('token', $request->code)
            ->delete();

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
            /// TODO: verify transaction before updating the transaction table
            $transaction = $this->transactionService->registerTransaction($response, $transferType);

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
        $recieving_account_id = trim($request->get("recieving_account_id"));
        $amount = trim($request->get("amount"));
        $type = trim($request->get("type"));
        $note = trim($request->get("note"));
        $beneficiary_account_holder_name = trim($request->get("beneficiary_account_holder_name"));
        $beneficiary_account_number = trim($request->get("beneficiary_account_number"));
        $beneficiary_bank_code = trim($request->get("beneficiary_bank_code"));
        $beneficiary_first_name = trim($request->get("beneficiary_first_name"));
        $beneficiary_last_name = trim($request->get("beneficiary_last_name"));
        $beneficiary_type = trim($request->get("beneficiary_type"));
        $beneficiary_phone = trim($request->get("beneficiary_phone"));
        $beneficiary_email = trim($request->get("beneficiary_email"));

        $sender = $this->performSecurityCheckOnSender($account_id, $recieving_account_id, $amount);
        $user = auth()->user();
        $reference = CodeHelper::generateSecureReference();
        $requestData = new Request([
            'transferType' => 'BANK_ACCOUNT_TRANSFER',
            'amount' => $amount,
            'account_id' => $account_id,
        ]);
        $validatedTransfer = $this->transferResourse->validateTransfer($requestData);
        $validatedTransferContent = json_decode($validatedTransfer->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($validatedTransferContent['data']['charge'])) {
            throw new AppException('Invalid response from validateTransfer.');
        }

        $charge = $validatedTransferContent['data']['charge'];
        // check if amount - charge is less than 100 which is the minimum enternal transferable
        $amount_sendable = (int) $amount - (int) $charge;

        if($amount_sendable < 100){
            throw new AppException("Minimum destination amount should not be less than (NGN 100.00).");
        }

        $payload = [
            'amount' => $amount_sendable,
            'beneficiary' => [
                'accountHolderName' => $beneficiary_account_holder_name,
                'accountNumber' => $beneficiary_account_number,
                'bankCode' => $beneficiary_bank_code,
                'firstName' => $beneficiary_first_name,
                'lastName' => $beneficiary_last_name,
                'type' => $beneficiary_type,
                'country' => "NG",
                'phone' => $beneficiary_phone,
                'email' => $beneficiary_email,
            ],
            'customerReference' => $reference,
            'description' => $note,
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
        ];

        $fincra_res =
         $this->fincraService->runTransfer(TransferType::BANK_ACCOUNT_TRANSFER, $payload);

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
            'note' => "[NIP/Transfer Digitwhale Innovations Limited] | " . $note,
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
                'note' => "[Digitwhale/Transfer Digitwhale Innovations Limited] | " . $note,
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
            throw new AppException("Invalid account id or account not found [SE].");
        }

        if ($account->enable) {
            throw new AppException('[Account Disabled] - Account cannot transacts at the moment. Contact customer support');
        }

        if ($account->pnd) {
            throw new AppException('Account cannot transact at the moment. Contact customer support.');
        }

        /// checks if the amount send today is equal to the the daily sendable limit
        if ($account->daily_transaction_count == $account->daily_transaction_limit) {
            throw new AppException('Daily Transaction limit exceeded. Try again later.');
        }

        if ($account->blacklisted) {
            throw new AppException('[Account Blacklisted] - Account cannot transacts at the moment. Contact customer support. MESSAGE: ' . $account->blacklist_text);
        }

        if ($reciever_account_id == $account->account_id) {
            // $account->update([
            //     'blacklisted' => true,
            //     'blacklist_text' => 'Self transfer perfomed [Threat Level 4/5].'
            // ]);
            // DB::commit();
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
            throw new AppException("Invalid account id or account not found [RE].");
        }

        if ($recieving_account->pnc) {
            throw new AppException('Account cannot recieve funds at the moment. Contact customer support.');
        }

        return $recieving_account;

    }

}