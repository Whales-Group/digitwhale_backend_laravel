<?php

namespace App\Modules\TransferModule\Services;

use App\Enums\ErrorCode;
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
use App\Models\User;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransferService
{
    protected FincraService $fincraService;
    protected PaystackService $paystackService;
    protected FlutterWaveService $flutterWaveService;
    protected TransferResourcesService $transferResourse;
    protected TransactionService $transactionService;

    public function __construct()
    {
        $this->fincraService = FincraService::getInstance();
        $this->paystackService = PaystackService::getInstance();
        $this->flutterWaveService = FlutterWaveService::getInstance();
        $this->transactionService = new TransactionService();
        $this->transferResourse = new TransferResourcesService();
    }

    public function transfer(Request $request, string $account_id)
    {
        $validator = $this->validateRequest($request);
        if ($validator->fails()) {
            return ResponseHelper::error(
                message: "Validation failed",
                error: $validator->errors()->toArray()
            );
        }

        $user = auth()->user();
        if (!$this->validateTransferCode($user->email, $request->code, $request->amount)) {
            return ResponseHelper::unprocessableEntity(
                message: "Invalidated Transfer.",
                error: ["transfer_code" => ["The transfer is invalid."]]
            );
        }

        DB::beginTransaction();
        $lock = Cache::lock('transfer_lock_' . $user->id, 10);

        try {
            if (!$lock->get()) {
                throw new AppException('Too many requests, please try again.');
            }

            $transferType = TransferType::from($request->transfer_type);
            $account = $this->validateSenderAccount($account_id, $request->recieving_account_id, $request->amount);

            $response = match ($transferType) {
                TransferType::WHALE_TO_WHALE => $this->handleInternalTransfer($request, $account_id),
                default => $this->handleExternalTransfer($request, $account, $transferType)
            };

            $transaction = $this->transactionService->registerTransaction($response, $transferType);

            DB::commit();

            return ResponseHelper::success(message: "Transfer Successful", data: $transaction);
        } catch (ClientException $e) {

            $responseBody = $e->getResponse()->getBody()->getContents();

            $errorData = json_decode($responseBody, true);

            if ($errorData['errorType'] === "NO_ENOUGH_MONEY_IN_WALLET") {
                throw new CodedException(ErrorCode::INSUFFICIENT_PROVIDER_BALANCE);
            }

            DB::rollBack();
            AppLog::error("transfer error", $e->getMessage());
            DB::commit();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            AppLog::error("transfer error", $e->getMessage());
            DB::commit();
            throw new AppException($e->getMessage());
        } finally {
            $lock?->release();
        }
    }

    protected function validateRequest(Request $request)
    {
        return Validator::make($request->all(), [
            "code" => "required|string",
            "transfer_type" => "required|string",
            "amount" => "required|integer",
            "recieving_account_id" => "sometimes|string|required_if:transfer_type,corporate",
        ]);
    }

    protected function validateTransferCode(string $email, string $code, int $amount): bool
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $code)
            ->first();

        if (!$record) {
            throw new AppException("Invalid or expired transfer session.");
        }

        if ((int) $record->amount !== ($amount + $record->charge)) {
            throw new AppException("Transfer amount mismatch.");
        }

        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $code)
            ->delete();

        return true;
    }

    public function validateSenderAccount(string $accountId, ?string $receiverId, int $amount): Account
    {
        $account = Account::where('user_id', auth()->id())
            ->where('account_id', $accountId)
            ->firstOrFail();

        if ($account->enable || $account->pnd || $account->blacklisted) {
            throw new AppException('Account is restricted from performing transfers.');
        }

        if ($account->daily_transaction_count >= $account->daily_transaction_limit) {
            throw new AppException('Daily transaction limit exceeded.');
        }

        if ($receiverId === $account->account_id) {
            throw new AppException('Self transfers are not allowed.');
        }

        //TODO: unncomment this.
        if ($amount > $account->balance) {
            throw new AppException('Insufficient funds.');
        }

        return $account;
    }

    private function handleInternalTransfer(Request $request, string $account_id): array
    {
        $receiverAccount = $this->validateReceiverAccount($request->recieving_account_id);
        $senderAccount = $this->validateSenderAccount($account_id, $request->recieving_account_id, $request->amount);

        if ($receiverAccount->currency !== $senderAccount->currency) {
            throw new AppException("Currency mismatch between sender and receiver accounts.");
        }

        $this->updateBalances($senderAccount, $receiverAccount, $request->amount);

        return $this->buildInternalTransactionData(
            $senderAccount,
            $receiverAccount,
            $request->amount,
            $request->note,
            $request->type
        );
    }

    private function validateReceiverAccount(string $accountId): Account
    {
        $account = Account::where('account_id', $accountId)
            ->firstOrFail();

        if ($account->pnc) {
            throw new AppException('Recipient account cannot receive funds.');
        }

        return $account;
    }

    private function updateBalances(Account $sender, Account $receiver, int $amount): void
    {
        // $sender->decrement('balance', $amount);
        $receiver->increment('balance', $amount);
    }

    private function buildInternalTransactionData(Account $sender, Account $receiver, int $amount, string $note, string $type): array
    {
        $receiverUser = User::findOrFail($receiver->user_id);

        return [
            'currency' => $sender->currency,
            'to_sys_account_id' => $receiver->id,
            'to_user_name' => $receiverUser->profile_type === 'personal'
                ? $receiver->validated_name
                : $receiverUser->business_name,
            'to_user_email' => $receiverUser->email,
            'to_bank_name' => $receiver->service_bank,
            'to_bank_code' => $receiver->service_bank,
            'to_account_number' => $receiver->account_number,
            'transaction_reference' => CodeHelper::generateSecureReference(),
            'status' => 'successful',
            'type' => $type,
            'charge' => 0,
            'amount' => $amount,
            'note' => "[Digitwhale/Transfer] | " . $note,
            'entry_type' => 'debit',
        ];
    }

    protected function handleExternalTransfer(Request $request, Account $account, TransferType $transferType): array
    {
        return match ($account->service_provider) {
            ServiceProvider::FINCRA => $this->handleFincraTransfer($request, $account),
            ServiceProvider::FLUTTERWAVE => $this->handleFlutterWaveTransfer($request, $account),
            ServiceProvider::PAYSTACK => throw new AppException("Service Unavailable. Contact support to switch service provider."),
            default => $this->handleFincraTransfer($request, $account),
        };
    }


    private function validateTransferData(Request $request, Account $account): array
    {
        $validationResponse = $this->transferResourse->validateTransfer(new Request([
            'transferType' => 'BANK_ACCOUNT_TRANSFER',
            'amount' => $request->amount,
            'account_id' => $account->account_id,
        ]), true);

        return [
            'amount' => (int) $request->amount + (int) $validationResponse['charge'],
            'charge' => $validationResponse['charge'],
        ];
    }

    private function buildTransactionData(
        Account $account,
        int $amount,
        int $charge,
        string $status,
        string $note,
        array $beneficiary,
        string $type
    ): array {
        return [
            'currency' => $account->currency,
            'to_sys_account_id' => null,
            'to_user_name' => $beneficiary['accountHolderName'] ?? '',
            'to_user_email' => $beneficiary['email'] ?? '',
            'to_bank_name' => request()->beneficiary_bank,
            'to_bank_code' => $beneficiary['bankCode'] ?? '',
            'to_account_number' => $beneficiary['accountNumber'] ?? '',
            'transaction_reference' => CodeHelper::generateSecureReference(),
            'status' => $status,
            'type' => $type,
            'amount' => $amount,
            'note' => "[NIP/Transfer] | " . $note,
            'entry_type' => 'debit',
            'charge' => $charge,
        ];
    }

    private function handleFincraTransfer(Request $request, Account $account): array
    {
        $validatedData = $this->validateTransferData($request, $account);
        $payload = $this->initiateFincraTransfer($request, $account, $validatedData);

        $transferResponse = $this->fincraService->runTransfer(
            TransferType::BANK_ACCOUNT_TRANSFER,
            $payload
        );

        // TODO: comment this 
        // $transferResponse = ['data' => ['status' => 'successful']];

        return $this->buildTransactionData(
            $account,
            $validatedData['amount'],
            $validatedData['charge'],
            $transferResponse['data']['status'],
            $request->note,
            $payload['beneficiary'],
            $request->type
        );
    }

    private function initiateFincraTransfer(Request $request, Account $account, array $validatedData): array
    {
        $user = auth()->user();
        $senderName = $user->profile_type === 'personal'
            ? $user->full_name
            : $user->business_name;

        return [
            'amount' => $validatedData['amount'] - $validatedData['charge'],
            'beneficiary' => [
                'accountHolderName' => $request->beneficiary_account_holder_name,
                'accountNumber' => $request->beneficiary_account_number,
                'bankCode' => $request->beneficiary_bank_code,
                'firstName' => $request->beneficiary_first_name,
                'lastName' => $request->beneficiary_last_name,
                'type' => $request->beneficiary_type,
                'country' => "NG",
                'phone' => $request->beneficiary_phone,
                'email' => $request->beneficiary_email,
            ],
            'customerReference' => CodeHelper::generateSecureReference(),
            'description' => $request->note,
            'destinationCurrency' => "NGN",
            'paymentDestination' => 'bank_account',
            'sourceCurrency' => "NGN",
            'sender' => [
                'name' => $senderName,
                'email' => $account->email,
            ],
        ];
    }

    private function handleFlutterWaveTransfer(Request $request, Account $account): array
    {
        $validatedData = $this->validateTransferData($request, $account);
        $transferResponse = $this->initiateFlutterWaveTransfer($request, $validatedData);

        return $this->buildTransactionData(
            $account,
            $validatedData['amount'],
            $validatedData['charge'],
            $transferResponse['data']['status'],
            $request->note,
            [
                'accountHolderName' => $request->beneficiary_account_holder_name,
                'accountNumber' => $request->beneficiary_account_number,
                'bankCode' => $request->beneficiary_bank_code,
                'email' => $request->beneficiary_email
            ],
            $request->type
        );
    }

    private function initiateFlutterWaveTransfer(Request $request, array $validatedData): array
    {
        // Dummy instance for testing without actual API call
        // return [
        //     'data' => [
        //         'status' => 'successful',
        //         'reference' => CodeHelper::generateSecureReference(),
        //         'amount' => $validatedData['amount'],
        //         'currency' => 'NGN',
        //         'account_bank' => $request->beneficiary_bank_code,
        //         'account_number' => $request->beneficiary_account_number,
        //         'beneficiary_name' => $request->beneficiary_account_holder_name,
        //         'narration' => $request->note,
        //     ]
        // ];

        return $this->flutterWaveService->runTransfer([
            "account_bank" => $request->beneficiary_bank_code,
            "account_number" => $request->beneficiary_account_number,
            "amount" => $validatedData['amount'] - $validatedData['charge'],
            "currency" => "NGN",
            "beneficiary" => null,
            "beneficiary_name" => $request->beneficiary_account_holder_name,
            "reference" => CodeHelper::generateSecureReference(),
            "debit_currency" => "NGN",
            "callback_url" => "https://webhook.site/5f9a659a-11a2-4925-89cf-8a59ea6a019a",
            "narration" => $request->note,

        ]);
    }
}
