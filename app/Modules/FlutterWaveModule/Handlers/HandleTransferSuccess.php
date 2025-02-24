<?php

namespace App\Modules\FlutterWaveModule\Handlers;

use App\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\AppLog;
use App\Models\TransactionEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HandleTransferSuccess
{
    public static function handle(array $transactionData): ?JsonResponse
    {
        // Log received transaction data
        AppLog::info('Processing successful transfer', ['transactionData' => $transactionData]);

        // Ensure transaction does not already exist
        if (TransactionEntry::where('transaction_reference', $transactionData['reference'])->exists()) {
            AppLog::info('Duplicate transaction detected', ['reference' => $transactionData['reference']]);
            return ResponseHelper::error('Duplicate transaction detected', 409);
        }

        // Find user account
        $account = Account::where("dedicated_account_id", $transactionData['virtualAccount'])->first();

        if (!$account) {
            AppLog::error("Account not found", ['virtualAccount' => $transactionData['virtualAccount']]);
            return ResponseHelper::error('Account not found', 404);
        }

        // Log account information
        AppLog::debug("Account found", ['account_id' => $account->id, 'user_id' => $account->user_id]);

        // Transaction fee (1% capped at 300 NGN)
        $fee = $transactionData['fee'];


        // Update user balance
        try {
            $prevBalance = $account->balance;
            $newBalance = $account->balance + ($transactionData['amountReceived']);
            $account->update(['balance' => $newBalance]);

            AppLog::info("Account credited successfully", [
                'account_id' => $account->id,
                'previous_balance' => $account->balance,
                'new_balance' => $newBalance,
                'amount_received' => $transactionData['amountReceived'],
                'fee' => $fee
            ]);
        } catch (\Exception $e) {
            AppLog::error("Failed to update account balance", ['error' => $e->getMessage()]);
            return ResponseHelper::error('Failed to update balance', 500);
        }

        // Store the successful transaction
        try {
            $transaction = TransactionEntry::create([
                'transaction_reference' => $transactionData['reference'],
                'from_user_name' => $transactionData['customerName'],
                'from_account' => $transactionData['senderAccountNumber'] ?? 'Unknown',
                'to_sys_account_id' => $account->id,
                'to_user_name' => $account->user->profile_type == 'personal'
                    ? trim("{$account->user->first_name} {$account->user->middle_name} {$account->user->last_name}")
                    : $account->user->business_name,
                'to_user_email' => $account->user->email,
                'to_bank_name' => $account->service_bank,
                'to_bank_code' => $account->service_bank,
                'to_account_number' => $account->account_number,
                'currency' => 'NAIRA',
                'amount' => $transactionData['destinationAmount'],
                'status' => 'successful',
                'type' => 'credit',
                'description' => $transactionData['description'] ?? 'Fund received',
                'timestamp' => now(),
                'entry_type' => 'credit',
                'charge' => $fee,
                'source_amount' => $transactionData['sourceAmount'],
                'amount_received' => $transactionData['amountReceived'],
                'from_bank' => $transactionData['senderBankName'],
                'source_currency' => "NAIRA",
                'destination_currency' => "NAIRA",
                'previous_balance' => $prevBalance,
                'new_balance' => $newBalance,
            ]);

            AppLog::info("Transaction recorded successfully", [
                'transaction_reference' => $transactionData['reference'],
                'amount' => $transactionData['destinationAmount'],
                'fee' => $fee
            ]);

            return ResponseHelper::success([
                'message' => $transaction['message'] ?? 'Transaction recorded successfully',
                'data' => $transaction['data'],
            ]);
        } catch (\Exception $e) {
            AppLog::error("Failed to record transaction", ['error' => $e->getMessage()]);
            return ResponseHelper::error('Failed to record transaction', 500);
        }
    }
}
