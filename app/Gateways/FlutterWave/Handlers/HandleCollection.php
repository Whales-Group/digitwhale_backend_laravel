<?php

namespace App\Gateways\FlutterWave\Handlers;

use App\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\AppLog;
use App\Models\TransactionEntry;
use Illuminate\Http\JsonResponse;

class HandleCollection
{
    public static function handle(array $eventData): ?JsonResponse
    {
        AppLog::info('Processing card payment webhook', ['event' => $eventData['event'], 'data' => $eventData['data']]);

        return $eventData['data']['status'] !== 'successful'
            ? self::handleFailedPayment($eventData['data'])
            : self::processSuccessfulPayment($eventData['data']);
    }

    private static function handleFailedPayment(array $paymentData): JsonResponse
    {
        AppLog::warning('Failed card payment detected', [
            'tx_ref' => $paymentData['tx_ref'],
            'status' => $paymentData['status'],
            'reason' => $paymentData['processor_response'] ?? 'Payment failed'
        ]);

        $transaction = TransactionEntry::firstOrCreate(
            ['transaction_reference' => $paymentData['tx_ref']],
            self::buildTransactionData($paymentData, 'failed')
        );

        return ResponseHelper::error(
            $paymentData['processor_response'] ?? 'Card payment failed',
            400,
            ['transaction' => $transaction]
        );
    }

    private static function processSuccessfulPayment(array $paymentData): JsonResponse
    {
        return TransactionEntry::where('transaction_reference', $paymentData['tx_ref'])->exists()
            ? self::handleDuplicatePayment($paymentData)
            : self::processNewPayment($paymentData);
    }

    private static function handleDuplicatePayment(array $paymentData): JsonResponse
    {
        AppLog::info('Duplicate payment detected', ['tx_ref' => $paymentData['tx_ref']]);
        return ResponseHelper::error('Duplicate payment detected', 409);
    }

    private static function processNewPayment(array $paymentData): JsonResponse
    {
        $account = Account::find($paymentData['account_id']);

        return !$account
            ? self::handleMissingAccount($paymentData)
            : self::creditAccount($account, $paymentData);
    }

    private static function handleMissingAccount(array $paymentData): JsonResponse
    {
        AppLog::error("Account not found", ['account_id' => $paymentData['account_id']]);
        return ResponseHelper::error('Account not found', 404);
    }

    private static function creditAccount(Account $account, array $paymentData): JsonResponse
    {
        $amountReceived = $paymentData['charged_amount'] - $paymentData['app_fee'];
        $prevBalance = $account->balance;
        $newBalance = $account->balance + $amountReceived;

        try {
            $account->update(['balance' => $newBalance]);
            AppLog::info("Account credited", [
                'account_id' => $account->id,
                'amount' => $amountReceived,
                'new_balance' => $newBalance
            ]);

            $transaction = self::recordTransaction($account, $paymentData, $prevBalance, $newBalance);
            return ResponseHelper::success([
                'message' => $paymentData['processor_response'] ?? 'Payment successful',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            AppLog::error("Balance update failed", ['error' => $e->getMessage()]);
            return ResponseHelper::error('Failed to process payment', 500);
        }
    }

    private static function buildTransactionData(array $paymentData, string $status): array
    {
        $card = $paymentData['card'] ?? [];
        $customer = $paymentData['customer'] ?? [];

        return [
            'transaction_reference' => $paymentData['tx_ref'],
            'flw_reference' => $paymentData['flw_ref'],
            'from_user_name' => $customer['name'] ?? 'Card User',
            'from_account' => $card['last_4digits'] ? '**** **** **** ' . $card['last_4digits'] : 'Unknown Card',
            'to_sys_account_id' => $paymentData['account_id'],
            'to_user_name' => $customer['name'] ?? 'Merchant',
            'currency' => $paymentData['currency'],
            'amount' => $paymentData['amount'],
            'charged_amount' => $paymentData['charged_amount'],
            'status' => $status,
            'type' => 'card',
            'description' => $paymentData['narration'] ?? 'Card payment',
            'timestamp' => $paymentData['created_at'] ?? now(),
            'entry_type' => 'credit',
            'charge' => $paymentData['app_fee'],
            'payment_type' => $paymentData['payment_type'],
            'card_details' => json_encode($card),
            'ip_address' => $paymentData['ip'],
            'processor_response' => $paymentData['processor_response'],
            'auth_model' => $paymentData['auth_model']
        ];
    }

    private static function recordTransaction(
        Account $account,
        array $paymentData,
        float $prevBalance,
        float $newBalance
    ): TransactionEntry {
        $transactionData = self::buildTransactionData($paymentData, 'successful');
        
        $transactionData = array_merge($transactionData, [
            'to_sys_account_id' => $account->id,
            'to_user_name' => $account->user->profile_type == 'personal'
                ? trim("{$account->user->first_name} {$account->user->last_name}")
                : $account->user->business_name,
            'to_user_email' => $account->user->email,
            'amount_received' => $paymentData['charged_amount'] - $paymentData['app_fee'],
            'previous_balance' => $prevBalance,
            'new_balance' => $newBalance
        ]);

        return TransactionEntry::create($transactionData);
    }
}