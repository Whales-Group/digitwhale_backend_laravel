<?php

namespace App\Modules\TransferModule\Services;

use App\Common\Helpers\DateHelper;
use App\Common\Helpers\ResponseHelper;
use App\Exceptions\AppException;
use App\Models\Account;
use App\Models\TransactionEntry;
use App\Modules\FincraModule\Services\FincraService;
use App\Modules\PaystackModule\Services\PaystackService;
use Illuminate\Http\Request;

class TransactionService
{
    public FincraService $fincraService;
    public PaystackService $paystackService;

    public function __construct()
    {
        $this->fincraService = FincraService::getInstance();
        $this->paystackService = PaystackService::getInstance();
    }


    public function registerTransaction(array $data)
    {
        $user = auth()->user();
        $account = Account::where("user_id", $user->id)->where('currency', $data['currency'])->first();

        if (!$account) {
            throw new AppException("Account not found.");
        }

        $registry = [
            'from_sys_account_id' => $account->id,
            'from_account' => $account->account_number,
            'from_user_name' => $user->profile_type != 'personal'
                ? $user->business_name
                : $user->first_name
                . " "
                . $user->middle_name
                . " "
                . $user->last_name,
            'from_user_email' => $user->email,
            'currency' => $data['currency'],
            'to_sys_account_id' => $data['to_sys_account_id'],
            'to_user_name' => $data['to_user_name'],
            'to_user_email' => $data['to_user_email'],
            'to_bank_name' => $data['to_bank_name'],
            'to_bank_code' => $data['to_bank_code'],
            'to_account_number' => $data['to_account_number'],
            'transaction_reference' => $data['transaction_reference'],
            'status' => $data['status'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'timestamp' => DateHelper::now(),
            'description' => $data['note'],
            'entry_type' => $data['entry_type'],
        ];

        $transaction = TransactionEntry::create($registry);

        return $transaction;
    }

    public function getAllTransactions(Request $request)
    {
        $limit = $request->get('limit', 10); // Default pagination limit is 10
        $page = $request->get('page', 1); // Default page number is 1

        $query = TransactionEntry::query();

        // Filtering by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filtering by amount range
        if ($request->has('from_amount')) {
            $query->where('amount', '>=', $request->from_amount);
        }
        if ($request->has('to_amount')) {
            $query->where('amount', '<=', $request->to_amount);
        }

        // Filtering by date range
        if ($request->has('from_date')) {
            $query->whereDate('timestamp', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('timestamp', '<=', $request->to_date);
        }

        // Searching by query_string (applies to user names, emails, or account numbers)
        if ($request->has('query_string')) {
            $searchTerm = $request->query_string;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('from_user_name', 'LIKE', "%$searchTerm%")
                    ->orWhere('to_user_name', 'LIKE', "%$searchTerm%")
                    ->orWhere('from_user_email', 'LIKE', "%$searchTerm%")
                    ->orWhere('to_user_email', 'LIKE', "%$searchTerm%")
                    ->orWhere('from_account', 'LIKE', "%$searchTerm%")
                    ->orWhere('to_account_number', 'LIKE', "%$searchTerm%");
            });
        }

        // Paginate results
        $transactions = $query->orderBy('timestamp', 'desc')->paginate($limit, ['*'], 'page', $page);

        return response()->json($transactions);
    }

    public function getTransactionDetails(Request $request)
    {
        $request->validate([
            'transaction_reference' => 'required|string|exists:transaction_entries,transaction_reference',
        ]);

        // Retrieve transaction using the reference
        $transaction = TransactionEntry::where('transaction_reference', $request->transaction_reference)->first();

        if (!$transaction) {
            return ResponseHelper::notFound(
                message: 'Transaction not found'
            );
        }

        return ResponseHelper::success(
            message: 'Transaction details retrieved successfully',
            data: $transaction
        );
    }



}