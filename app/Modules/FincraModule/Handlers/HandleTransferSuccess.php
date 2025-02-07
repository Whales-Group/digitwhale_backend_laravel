<?php

namespace App\Modules\FincraModule\Handlers;

use App\Common\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\TransactionEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HandleTransferSuccess
{
    public static function handle(array $transactionData): ?JsonResponse
    {
        // Log the event
        Log::info('Processing successful transfer:', $transactionData);

        // Ensure transaction does not already exist
        if (TransactionEntry::where('transaction_reference', $transactionData['reference'])->exists()) {
            return ResponseHelper::error('Duplicate transaction detected', 409);
        }

        // credit user 
        $account = Account::where("dedicated_account_id", $transactionData['virtualAccount'])->first();
        $newBalance = $account->balance + $transactionData['amountReceived'];
        $account->update([
            'balance' => $newBalance,
        ]);


        // Store the successful transaction
        $transaction = TransactionEntry::create([
            'transaction_reference' => $transactionData['reference'],
            'from_user_name' => $transactionData['customerName'],
            'from_account' => $transactionData['senderAccountNumber'] ?? 'Unknown',
            'to_sys_account_id' => $account->id,
            'to_user_name' => $account->user->profile_type == 'personal'
                ? $account->user->first_name . " " . $account->user->middle_name . " " . $account->user->last_name
                : $account->user->business_name,
            'to_user_email' => $account->user->email,
            'to_bank_name' => $account->service_bank,
            'to_bank_code' => $account->service_bank,
            'to_account_number' => $account->account_number,
            'currency' => $transactionData['sourceCurrency'],
            'amount' => $transactionData['destinationAmount'],
            'status' => 'successful',
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
        ]);

        return ResponseHelper::success([
            'message' => 'Transaction recorded successfully',
            'data' => $transaction,
        ]);
    }
}
