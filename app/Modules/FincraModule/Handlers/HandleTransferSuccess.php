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
            'to_sys_account_id' => null,
            'currency' => $transactionData['sourceCurrency'],
            'amount' => $transactionData['amountReceived'],
            'status' => 'successful',
            'type' => 'deposit',
            'description' => $transactionData['description'] ?? 'Fund received',
            'timestamp' => now(),
            'entry_type' => 'credit',
        ]);

        return ResponseHelper::success([
            'message' => 'Transaction recorded successfully',
            'data' => $transaction,
        ]);
    }
}
