<?php

namespace App\Modules\FlutterWaveModule\Handlers;

use App\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\TransactionEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HandleTransferFailed
{
    public static function handle(array $transactionData): ?JsonResponse
    {
        // Log received transaction data
        Log::info('Processing failed transfer', ['transactionData' => $transactionData]);

        // Ensure transaction does not already exist
        if (TransactionEntry::where('transaction_reference', $transactionData['reference'])->exists()) {
            Log::info('Duplicate transaction detected', ['reference' => $transactionData['reference']]);
            return ResponseHelper::error('Duplicate transaction detected', 409);
        }

        // Find user account
        $account = Account::where("dedicated_account_id", $transactionData['virtualAccount'])->first();

        if (!$account) {
            Log::error("Account not found", ['virtualAccount' => $transactionData['virtualAccount']]);
            return ResponseHelper::error('Account not found', 404);
        }

        // Log account information
        Log::debug("Account found", ['account_id' => $account->id, 'user_id' => $account->user_id]);

        // Update user balance
        try {

            Log::info("Account credited failed", [
                'account_id' => $account->id,
                'previous_balance' => $account->balance,
                'amount_received' => $transactionData['amountReceived']
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update account balance", ['error' => $e->getMessage()]);
            return ResponseHelper::error('Failed to update balance', 500);
        }

        // Store the failed transaction
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
                'currency' => $transactionData['sourceCurrency'],
                'amount' => $transactionData['destinationAmount'],
                'status' => 'failed',
                'type' => 'deposit',
                'description' => $transactionData['description'] ?? 'Fund received',
                'timestamp' => now(),
                'entry_type' => 'credit',
                'charge' => $transactionData['fee'],
                'source_amount' => $transactionData['sourceAmount'],
                'amount_received' => $transactionData['amountReceived'],
                'from_bank' => $transactionData['senderBankName'],
                'source_currency' => $transactionData['sourceCurrency'],
                'destination_currency' => $transactionData['destinationCurrency'],
                'previous_balance' => $account->balance,
                'new_balance' => $account->balance,
            ]);

            Log::info("Transaction recorded failed", [
                'transaction_reference' => $transactionData['reference'],
                'amount' => $transactionData['destinationAmount']
            ]);

            return ResponseHelper::success([
                'message' => 'Transaction recorded failed',
                'data' => $transaction,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to record transaction", ['error' => $e->getMessage()]);
            return ResponseHelper::error('Failed to record transaction', 500);
        }
    }
}
