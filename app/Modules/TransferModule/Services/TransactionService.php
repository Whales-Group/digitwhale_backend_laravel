<?php

namespace App\Modules\TransferModule\Services;

use App\Enums\TransferType;
use App\Exceptions\AppException;
use App\Gateways\Fincra\Services\FincraService;
use App\Gateways\Paystack\Services\PaystackService;
use App\Helpers\DateHelper;
use App\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\TransactionEntry;
use Illuminate\Http\JsonResponse;
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

    public function registerTransaction(array $data, TransferType $transferType)
    {
        $user = auth()->user();
        $account = Account::where("user_id", $user->id)->where('currency', $data['currency'])->first();

        if (!$account) {
            throw new AppException("Account not found.");
        }

        // Calculate the transaction fee
        $charge = $transferType == TransferType::WHALE_TO_WHALE ? 0 : $data['charge'];

        // Resolve balances
        $previousBalance = $account->balance;

        $newBalance = $transferType == TransferType::WHALE_TO_WHALE
            ? $account->balance - $data['amount']
            : $account->balance - ($data['amount'] + $charge);

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
            'charge' => $charge,
            'source_amount' => $data['amount'] + $charge,
            'amount_received' => $data['amount'],
            'from_bank' => $account->service_bank,
            'source_currency' => $account->currency,
            'destination_currency' => 'NAIRA',
            'previous_balance' => $previousBalance,
            'new_balance' => $newBalance,
        ];

        $transaction = TransactionEntry::create($registry);

        return $transaction;
    }

    // public function calculateTransactionFee(float $amount, string $currency, Account $account, TransferType $transferType): float
    // {
    //     if ($currency !== 'NGN') {
    //         return 0.0;
    //     }

    //     if ($transferType == TransferType::WHALE_TO_WHALE) {
    //         return 0.0;
    //     }

    //     $accountType = ServiceProvider::tryFrom($account->service_provider)
    //         ?? throw new AppException("Invalid Service Provider");

    //     // Calculate transfer fee based on provider
    //     $transferFee = match ($accountType) {
    //         ServiceProvider::FINCRA => 50,
    //         ServiceProvider::PAYSTACK => 10,
    //         ServiceProvider::FLUTTERWAVE =>$amount <= 5000 ? 10 : ($amount <= 50000 ? 25 : 50),
    //         default => throw new AppException("Invalid account service provider."),
    //     };

    //     return $transferFee; 
    // }

    /**
     * Get transactions based on query parameters or return all paginated.
     *
     * @param Request $request
     * @return JsonResponse
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
            $accountIds = $user->accounts->pluck('id')->toArray();
            $query->whereIn('from_sys_account_id', $accountIds)
                ->orWhereIn('to_sys_account_id', $accountIds);
        });

        if ($queryParams['expand'] && in_array(strtoupper($queryParams['expand']), $allowedExpandValues)) {
            switch (strtoupper($queryParams['expand'])) {
                case 'RECENT':
                    return ResponseHelper::success($query->orderBy('timestamp', 'desc')->take(4)->get() ?? []);
                case 'CREDIT':
                    $query->where(function ($query) use ($user) {
                        $accountIds = $user->accounts->pluck('id')->toArray();
                        $query->whereIn('to_sys_account_id', $accountIds);
                    });
                    break;
                case 'DEBIT':
                    $query->where(function ($query) use ($user) {
                        $accountIds = $user->accounts->pluck('id')->toArray();
                        $query->whereIn('from_sys_account_id', $accountIds)
                            ->where('entry_type', 'debit');
                    });
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
