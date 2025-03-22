<?php

namespace App\Modules\TransferModule\Services;

use App\Enums\ErrorCode;
use App\Enums\ServiceProvider;
use App\Enums\TransferType;
use App\Exceptions\AppException;
use App\Exceptions\CodedException;
use App\Helpers\CodeHelper;
use App\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\Beneficiary;
use App\Models\User;
use App\Modules\FincraModule\Services\FincraService;
use App\Modules\FlutterWaveModule\Services\FlutterWaveService;
use App\Modules\PaystackModule\Services\PaystackService;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Validator;

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
        if (!$this->validateTransferCode($user->email, $request->code)) {
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

            $this->transactionService->registerTransaction($response, $transferType);
            DB::commit();

            return ResponseHelper::success(message: "Transfer Successful", data: $response);
        } catch (Exception $e) {
            DB::rollBack();
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

    protected function validateTransferCode(string $email, string $code): bool
    {
        return DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $code)
            ->delete() > 0;
    }

    protected function handleExternalTransfer(Request $request, Account $account, TransferType $transferType): array
    {
        return match ($account->service_provider) {
            ServiceProvider::FINCRA => $this->handleFincraTransfer($request, $account),
            ServiceProvider::FLUTTERWAVE => $this->handleFlutterWaveTransfer($request, $account),
            ServiceProvider::PAYSTACK => throw new AppException("Service Unavailable. Contact support to switch service provider."),
            default => $this->handleFlutterWaveTransfer($request, $account),
        };
    }

    private function handleFincraTransfer(Request $request, Account $account): array
    {
        $validatedData = $this->validateTransferData($request, $account);
        $payload = $this->createFincraPayload($request, $account, $validatedData);

        $transferResponse = $this->fincraService->runTransfer(
            TransferType::BANK_ACCOUNT_TRANSFER,
            $payload
        );

        return $this->buildTransactionData(
            $account,
            $validatedData['amount'],
            $validatedData['charge'],
            $transferResponse['data']['status'],
            $request->note,
            $payload['beneficiary']
        );
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
            ]
        );
    }

    private function validateTransferData(Request $request, Account $account): array
    {
        $validationResponse = $this->transferResourse->validateTransfer(new Request([
            'transferType' => 'BANK_ACCOUNT_TRANSFER',
            'amount' => $request->amount,
            'account_id' => $account->account_id,
        ]));

        $responseData = json_decode($validationResponse->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($responseData['data']['charge'])) {
            throw new AppException('Invalid response from validateTransfer.');
        }

        $this->validateSendableAmount($request->amount, $responseData['data']['charge']);

        return [
            'amount' => $request->amount,
            'charge' => $responseData['data']['charge'],
            'sendable_amount' => (int) $request->amount - (int) $responseData['data']['charge']
        ];
    }

    private function validateSendableAmount(int $amount, int $charge): void
    {
        if (($amount - $charge) < 100) {
            throw new AppException("Minimum destination amount should not be less than (NGN 100.00).");
        }
    }

    private function createFincraPayload(Request $request, Account $account, array $validatedData): array
    {
        $user = auth()->user();
        $senderName = $user->profile_type === 'personal'
            ? $user->full_name
            : $user->business_name;

        return [
            'amount' => $validatedData['sendable_amount'],
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
    private function initiateFlutterWaveTransfer(Request $request, array $validatedData): array
    {
        return $this->flutterWaveService->runTransfer([
            "account_bank" => $request->beneficiary_bank_code,
            "account_number" => $request->beneficiary_account_number,
            "amount" => $validatedData['sendable_amount'],
            "narration" => $request->note,
            "currency" => "NGN",
            "reference" => CodeHelper::generateSecureReference(),
            "debit_currency" => "NGN"
        ]);
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
            $request->note
        );
    }

    private function updateBalances(Account $sender, Account $receiver, int $amount): void
    {
        $sender->decrement('balance', $amount);
        $receiver->increment('balance', $amount);
    }

    private function buildInternalTransactionData(Account $sender, Account $receiver, int $amount, string $note): array
    {
        $receiverUser = User::findOrFail($receiver->user_id);

        return [
            'currency' => $sender->currency,
            'to_sys_account_id' => $receiver->id,
            'to_user_name' => $receiverUser->profile_type === 'personal'
                ? $receiverUser->full_name
                : $receiverUser->business_name,
            'to_user_email' => $receiverUser->email,
            'to_bank_name' => $receiver->service_bank,
            'to_bank_code' => 'Internal Transfer',
            'to_account_number' => $receiver->account_number,
            'transaction_reference' => CodeHelper::generateSecureReference(),
            'status' => 'successful',
            'type' => 'internal',
            'amount' => $amount,
            'note' => "[Digitwhale/Transfer] | " . $note,
            'entry_type' => 'debit',
        ];
    }

    private function buildTransactionData(
        Account $account,
        int $amount,
        int $charge,
        string $status,
        string $note,
        array $beneficiary
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
            'type' => 'external',
            'amount' => $amount,
            'note' => "[NIP/Transfer] | " . $note,
            'entry_type' => 'debit',
            'charge' => $charge,
        ];
    }

    private function validateSenderAccount(string $accountId, ?string $receiverId, int $amount): Account
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

        if ($amount > $account->balance) {
            throw new AppException('Insufficient funds.');
        }

        return $account;
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
}