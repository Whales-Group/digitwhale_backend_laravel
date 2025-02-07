<?php

namespace App\Modules\FincraModule\Handlers;

use App\Common\Helpers\ResponseHelper;
use App\Models\TransactionEntry;
use Illuminate\Http\JsonResponse;
use App\Models\FailedTransaction;
use Illuminate\Support\Facades\Log;

class HandleTransferFailed
{
    public static function handle(array $transactionData): ?JsonResponse
    {
        // Log the failed transaction
        Log::warning('Processing failed transfer:', $transactionData);
        
        // Store the failed transaction
        TransactionEntry::create([
            'transaction_reference' => $transactionData['reference'],
            'from_user_name' => $transactionData['customerName'],
            'from_account' => $transactionData['senderAccountNumber'] ?? 'Unknown',
            'currency' => $transactionData['sourceCurrency'],
            'amount' => $transactionData['sourceAmount'],
            'status' => 'failed',
            'reason' => $transactionData['reason'] ?? 'Unknown failure reason',
            'timestamp' => now(),
        ]);

        return ResponseHelper::error('Transaction failed and recorded', 400);
    }
}
