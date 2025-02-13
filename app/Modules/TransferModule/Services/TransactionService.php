<?php

namespace App\Modules\TransferModule\Services;

use App\Common\Enums\TransferType;
use App\Common\Helpers\DateHelper;
use App\Common\Helpers\ResponseHelper;
use App\Exceptions\AppException;
use App\Models\Account;
use App\Models\TransactionEntry;
use App\Modules\FincraModule\Services\FincraService;
use App\Modules\PaystackModule\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TransactionService
{
    public FincraService $fincraService;
    public PaystackService $paystackService;

    public function __construct()
    {
        $this->fincraService = FincraService::getInstance();
        $this->paystackService = PaystackService::getInstance();
    }

    public function registerTransaction(array $data, TransferType $transferType)
    {
        $user = auth()->user();
        $account = Account::where("user_id", $user->id)->where('currency', $data['currency'])->first();

        if (!$account) {
            throw new AppException("Account not found.");
        }

        // Calculate the transaction fee
        $fee = $transferType == TransferType::WHALE_TO_WHALE ? 0 : $this->calculateTransactionFee($data['amount'], $data['currency']);

        // Calculate new balance
        if (($data['entry_type'] ?? 'debit') == 'debit') {
            $newBalance = $account->balance - ($data['amount'] + $fee);
        } else {
            $newBalance = $account->balance + $data['amount'];
        }

        $account->update([
            'balance' => $newBalance
        ]);

        $registry = [
            'from_sys_account_id' => $account->id,
            'from_account' => $account->account_number,
            'from_user_name' => $user->profile_type !== 'personal'
                ? $user->business_name
                : trim("{$user->first_name} {$user->middle_name} {$user->last_name}"),
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
            'entry_type' => $data['entry_type'] ?? 'debit',
            'charge' => $fee,
            'source_amount' => $data['amount'],
            'amount_received' => $data['amount'] - $fee,
            'from_bank' => $account->service_bank,
            'source_currency' => $account->currency,
            'destination_currency' => 'NAIRA',
            'previous_balance' => $account->balance,
            'new_balance' => $newBalance,
        ];

        $transaction = TransactionEntry::create($registry);

        return $transaction;
    }

    public function calculateTransactionFee(float $amount, string $currency): float
    {
        if ($currency !== 'NGN') {
            return 0.0;
        }

        $fee = $amount * 0.01; // 1% fee
        return 50.0; // Cap at 50 NGN
    }

    /**
     * Get transactions based on query parameters or return all paginated.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|array
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $user = auth()->user();

        $queryParams = [
            'expand' => $request->input('expand'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'type' => $request->input('type'),
            'amount1' => $request->input('amount1'),
            'amount2' => $request->input('amount2'),
            'query_string' => $request->input('query_string'),
        ];

        $allowedExpandValues = ['RECENT', 'CREDIT', 'DEBIT'];

        $query = TransactionEntry::query();

        $query->where(function ($query) use ($user) {
            $query->where('from_sys_account_id', 'like', '%' . $user->id . '%')
                ->orWhere('to_sys_account_id', 'like', '%' . $user->id . '%');
        });

        if ($queryParams['expand'] && in_array(strtoupper($queryParams['expand']), $allowedExpandValues)) {
            switch (strtoupper($queryParams['expand'])) {
                case 'RECENT':
                    return ResponseHelper::success($query->orderBy('timestamp', 'desc')->take(4)->get() ?? []);
                case 'CREDIT':
                    $query->where('entry_type', 'credit');
                    break;
                case 'DEBIT':
                    $query->where('entry_type', 'debit');
                    break;
            }
        }

        if ($queryParams['from_date'] && $queryParams['to_date']) {
            $fromDate = DateHelper::parse($queryParams['from_date']);
            $toDate = DateHelper::parse($queryParams['to_date']);
            $query->whereBetween('timestamp', [$fromDate, $toDate]);
        }

        if ($queryParams['type']) {
            $query->where('type', $queryParams['type']);
        }

        if ($queryParams['amount1'] && $queryParams['amount2']) {
            $amount1 = (float) $queryParams['amount1'];
            $amount2 = (float) $queryParams['amount2'];
            $query->whereBetween('amount', [$amount1, $amount2]);
        }

        if ($queryParams['query_string']) {
            $query->where('from_user_email', 'like', '%' . $queryParams['query_string'] . '%')
                ->orWhere('to_user_email', 'like', '%' . $queryParams['query_string'] . '%')
                ->orWhere('transaction_reference', 'like', '%' . $queryParams['query_string'] . '%');
        }

        // Paginate results
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $perPage = max(1, min(100, $perPage));

        return ResponseHelper::success($query->paginate($perPage, ['*'], 'page', $page));
    }
}