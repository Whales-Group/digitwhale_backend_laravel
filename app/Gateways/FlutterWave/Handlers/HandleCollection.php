<?php

namespace App\Gateways\FlutterWave\Handlers;

use App\Exceptions\AppException;
use App\Gateways\FlutterWave\Services\FlutterWaveService;
use App\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\TransactionEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HandleCollection
{
    public static function handle(array $eventData): ?JsonResponse
    {
        return $eventData['status'] !== 'successful'
            ? self::handleFailedPayment($eventData)
            : self::processSuccessfulPayment($eventData);
    }

    private static function handleFailedPayment(array $paymentData): JsonResponse
    {
        $account = self::getAccountFromPayment($paymentData);

        if (!$account) {
            Log::error("Failed Payment: Account not found", ['flw_ref' => $paymentData['flw_ref'], 'tx_ref' => $paymentData['tx_ref']]);
            return ResponseHelper::error('Account not found for failed payment', 404);
        }

        $amountReceived = $paymentData['charged_amount'] - $paymentData['app_fee'];
        $prevBalance = $account->balance;
        $newBalance = $account->balance + $amountReceived;

        $transaction = TransactionEntry::firstOrCreate(
            ['transaction_reference' => $paymentData['tx_ref']],
            self::buildTransactionData($account, $paymentData, $prevBalance, $newBalance)
        );

        return ResponseHelper::error(
            $paymentData['processor_response'] ?? 'Card payment failed',
            400,
            ['transaction' => $transaction]
        );
    }

    private static function processSuccessfulPayment(array $paymentData): JsonResponse
    {
        return TransactionEntry::where('transaction_reference', $paymentData['tx_ref'])->exists()
            ? self::handleDuplicatePayment($paymentData)
            : self::processNewPayment($paymentData);
    }

    private static function handleDuplicatePayment(array $paymentData): JsonResponse
    {
        Log::info('Duplicate payment detected', ['tx_ref' => $paymentData['tx_ref']]);
        return ResponseHelper::error('Duplicate payment detected', 409);
    }

    private static function processNewPayment(array $paymentData): JsonResponse
    {
        $account = self::getAccountFromPayment($paymentData);

        return !$account
            ? self::handleMissingAccount($paymentData)
            : self::creditAccount($account, $paymentData);
    }

    private static function handleMissingAccount(array $paymentData): JsonResponse
    {
        Log::error("Account not found", [
            'flw_ref' => $paymentData['flw_ref'] ?? null,
            'account_id' => $paymentData['account_id'] ?? null,
            'tx_ref' => $paymentData['tx_ref'] ?? null
        ]);

        return ResponseHelper::error('Account not found', 404);
    }

    private static function creditAccount(Account $account, array $paymentData): JsonResponse
    {
        $amountReceived = $paymentData['charged_amount'] - $paymentData['app_fee'];
        $prevBalance = $account->balance;
        $newBalance = $account->balance + $amountReceived;

        try {
            $account->update(['balance' => $newBalance]);

            Log::info("Account credited", [
                'account_id' => $account->id,
                'amount' => $amountReceived,
                'new_balance' => $newBalance
            ]);

            $transaction = self::recordTransaction($account, $paymentData, $prevBalance, $newBalance);
            return ResponseHelper::success([
                'message' => $paymentData['processor_response'] ?? 'Payment successful',
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            Log::error("Balance update failed", ['error' => $e->getMessage()]);
            return ResponseHelper::error('Failed to process payment', 500);
        }
    }

    /**
     * @throws AppException
     */
    private static function recordTransaction(
        Account $account,
        array   $transactionData,
        float   $prevBalance,
        float   $newBalance
    ): TransactionEntry
    {
        $verifiedTransaction = FlutterWaveService::getInstance()->verifyTransaction($transactionData['tx_ref']);

        return TransactionEntry::create([
            'transaction_reference' => $transactionData['tx_ref'],
            'from_user_name' => $verifiedTransaction["meta"]["originatorname"] ?? "***********",
            'from_account' => $verifiedTransaction["meta"]["originatoraccountnumber"],
            'to_sys_account_id' => $account->id,
            'to_user_name' => $account->user->profile_type == 'personal'
                ? trim("{$account->user->first_name} {$account->user->last_name}")
                : $account->user->business_name,
            'to_bank_name' => "Sterling Bank PLC",
            'to_bank_code' => "232",
            'to_account_number' => $account->account_number,
            'currency' => $transactionData['currency'],
            'amount' => $transactionData['amount'],
            'status' => strtolower($transactionData['status']),
            'type' => 'credit',
            'description' => "[Digitwhale/Transfer] | " . ($transactionData['narration'] ?? 'Fund received'),
            'timestamp' => $transactionData['created_at'] ?? now(),
            'entry_type' => 'credit',
            'charge' => $transactionData['app_fee'],
            'source_amount' => $transactionData['amount'],
            'amount_received' => $transactionData['amount'] - $transactionData['app_fee'],
            'from_bank' => $verifiedTransaction["meta"]["bankname"] ?? "****** Bank PLC",
            'source_currency' => $transactionData['currency'],
            'destination_currency' => $transactionData['currency'],
            'previous_balance' => $prevBalance,
            'new_balance' => $newBalance,
        ]);
    }

    private static function buildTransactionData(
        Account $account,
        array   $transactionData,
        float   $prevBalance,
        float   $newBalance
    ): array
    {
        $verifiedTransaction = FlutterWaveService::getInstance()->verifyTransaction($transactionData['tx_ref']);

        return [
            'transaction_reference' => $transactionData['tx_ref'],
            'from_user_name' => $verifiedTransaction["meta"]["originatorname"] ?? "***********",
            'from_account' => $verifiedTransaction["meta"]["originatoraccountnumber"],
            'to_sys_account_id' => $account->id,
            'to_user_name' => $account->user->profile_type == 'personal'
                ? trim("{$account->user->first_name} {$account->user->last_name}")
                : $account->user->business_name,
            'to_bank_name' => "Sterling Bank PLC",
            'to_bank_code' => "232",
            'to_account_number' => $account->account_number,
            'currency' => $transactionData['currency'],
            'amount' => $transactionData['amount'],
            'status' => strtolower($transactionData['status']),
            'type' => 'credit',
            'description' => "[Digitwhale/Transfer] | " . ($transactionData['narration'] ?? 'Fund received'),
            'timestamp' => $transactionData['created_at'] ?? now(),
            'entry_type' => 'credit',
            'charge' => $transactionData['app_fee'],
            'source_amount' => $transactionData['amount'],
            'amount_received' => $transactionData['amount'] - $transactionData['app_fee'],
            'from_bank' => $verifiedTransaction["meta"]["bankname"] ?? "****** Bank PLC",
            'source_currency' => $transactionData['currency'],
            'destination_currency' => $transactionData['currency'],
            'previous_balance' => $prevBalance,
            'new_balance' => $newBalance,
        ];
    }

    /**
     * Helper to safely get Account from payment payload
     */
    private static function getAccountFromPayment(array $paymentData): ?Account
    {
        $account_email = $paymentData['customer']['email'] ?? null;
        $flwRef = $paymentData['flw_ref'] ?? null;
        $accountId = $paymentData['account_id'] ?? null;

        $account = Account::where('email', $account_email)->first();

        if (!$account && $accountId) {
            $account = Account::find($accountId);
        }

        Log::info("Account lookup result", [
            'flw_ref' => $flwRef,
            'account_id' => $accountId,
            'account_found' => (bool) $account
        ]);

        return $account;
    }
}

// "flw_ref": "FLW-da93010f630240a7978e893af92fed62",
// "order_ref": "URF_1613406439309_370935",
// "account_number": "7824822527",
