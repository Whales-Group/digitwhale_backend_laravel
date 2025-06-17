<?php

namespace App\Gateways\Fincra\Handlers;

use App\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\AppLog;
use App\Models\TransactionEntry;
use Illuminate\Http\JsonResponse;

class HandleTransferSuccess
{
    public static function handle(array $transactionData): ?JsonResponse
    {
        AppLog::info('Processing transfer webhook', ['webhookData' => $transactionData]);

        // Immediately return if transfer failed
        return $transactionData['status'] !== 'successful'
            ? self::handleFailedTransfer($transactionData)
            : self::processSuccessfulTransfer($transactionData);
    }

    private static function handleFailedTransfer(array $transactionData): JsonResponse
    {
        AppLog::warning('Failed transfer detected', [
            'reference' => $transactionData['reference'],
            'status' => $transactionData['status'],
            'message' => $transactionData['complete_message'] ?? 'No failure reason provided'
        ]);

        // Still record failed transaction but don't credit account
        $transaction = TransactionEntry::firstOrCreate(
            ['transaction_reference' => $transactionData['reference']],
            [
                'status' => 'failed',
                'description' =>  "[Digitwhale/Collection] | Failed " ,
                'amount' => $transactionData['amountReceived'],
                'currency' => $transactionData['sourceCurrency'],
                'created_at' => $transactionData['initiatedAt'] ?? now()
            ]
        );

        return ResponseHelper::error(
            $transactionData['complete_message'] ?? 'Transfer failed',
            400,
            ['transaction' => $transaction]
        );
    }

    private static function processSuccessfulTransfer(array $transactionData): JsonResponse
    {
        return TransactionEntry::where('transaction_reference', $transactionData['reference'])->exists()
            ? self::handleDuplicateTransaction($transactionData)
            : self::processNewTransaction($transactionData);
    }

    private static function handleDuplicateTransaction(array $transactionData): JsonResponse
    {
        AppLog::info('Duplicate transaction detected', ['reference' => $transactionData['reference']]);
        return ResponseHelper::error('Duplicate transaction detected', 409);
    }

    private static function processNewTransaction(array $transactionData): JsonResponse
    {
        $account = Account::where('customer_id', 'like', substr($transactionData['virtualAccount'], 0, 6) . '%')
            ->first();

        return !$account
            ? self::handleMissingAccount($transactionData)
            : self::creditAccount($account, $transactionData);
    }

    private static function handleMissingAccount(array $transactionData): JsonResponse
    {
        AppLog::error("Account not found", ['customer_id' => $transactionData['virtualAccount']]);
        return ResponseHelper::error('Account not found', 404);
    }

    private static function creditAccount(Account $account, array $transactionData): JsonResponse
    {
        $amountReceived = $transactionData['amount'] - $transactionData['fee'];
        $prevBalance = $account->balance;
        $newBalance = $account->balance + $amountReceived;

        try {
            $account->update(['balance' => $newBalance]);
            AppLog::info("Account credited", [
                'account_id' => $account->id,
                'amount' => $amountReceived,
                'new_balance' => $newBalance
            ]);

            $transaction = self::recordTransaction($account, $transactionData, $prevBalance, $newBalance);
            return ResponseHelper::success([
                'message' => $transactionData['complete_message'] ?? 'Transfer successful',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            AppLog::error("Balance update failed", ['error' => $e->getMessage()]);
            return ResponseHelper::error('Failed to update balance', 500);
        }
    }

    private static function recordTransaction(
        Account $account,
        array $transactionData,
        float $prevBalance,
        float $newBalance
    ): TransactionEntry {
        return TransactionEntry::create([
            'transaction_reference' => $transactionData['reference'],
            'from_user_name' => $transactionData['fullname'],
            'from_account' => $transactionData['account_number'],
            'to_sys_account_id' => $account->id,
            'to_user_name' => $account->user->profile_type == 'personal'
                ? trim("{$account->user->first_name} {$account->user->last_name}")
                : $account->user->business_name,
            'to_bank_name' => $transactionData['bank_name'],
            'to_bank_code' => $transactionData['bank_code'],
            'to_account_number' => $account->account_number,
            'currency' => $transactionData['currency'],
            'amount' => $transactionData['amount'],
            'status' => strtolower($transactionData['status']),
            'type' => 'credit',
            'description' => $transactionData['narration'] ?? 'Fund received',
            'timestamp' => $transactionData['created_at'] ?? now(),
            'entry_type' => 'credit',
            'charge' => $transactionData['fee'],
            'source_amount' => $transactionData['amount'],
            'amount_received' => $transactionData['amount'] - $transactionData['fee'],
            'from_bank' => $transactionData['bank_name'],
            'source_currency' => $transactionData['currency'],
            'destination_currency' => $transactionData['currency'],
            'previous_balance' => $prevBalance,
            'new_balance' => $newBalance,
        ]);
    }
}