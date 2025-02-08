<?php

namespace App\Modules\TransferModule\Services;

use App\Common\Enums\TransferType;
use App\Common\Helpers\DateHelper;
use App\Exceptions\AppException;
use App\Models\Account;
use App\Models\TransactionEntry;
use App\Modules\FincraModule\Services\FincraService;
use App\Modules\PaystackModule\Services\PaystackService;

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

        // Calculate fee and final amount
        $feeData = $this->calculateTransactionFee($data['amount'], $transferType);
        $fee = $feeData['fee'];
        $finalAmount = $feeData['final_amount'];

        // Calculate new balance
        if (($data['entry_type'] ?? 'debit') === 'debit') {
            $newBalance = $account->balance - $finalAmount;
        } else if (($data['entry_type'] ?? 'debit') === 'credit') {
            $newBalance = $account->balance + (float) $data['amount'];
        }

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
            'amount_received' => $finalAmount,
            'from_bank' => $account->service_bank,
            'source_currency' => $account->currency,
            'destination_currency' => 'NGN',
            'previous_balance' => $account->balance,
            'new_balance' => $newBalance,
        ];

        $transaction = TransactionEntry::create($registry);

        return $transaction;
    }

    public function calculateTransactionFee(float $amount, TransferType $transferType): array
    {
        $feePercentage = 1.0; // 1% fee
        $fee = ($transferType == TransferType::WHALE_TO_WHALE) ? 0 : ($amount * ($feePercentage / 100));
        $finalAmount = $amount - $fee;

        return [
            'initial_amount' => $amount,
            'fee' => $fee,
            'final_amount' => $finalAmount,
        ];
    }
}
