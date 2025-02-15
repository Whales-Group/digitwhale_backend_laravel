<?php

namespace App\Modules\AccountModule\Services;

use App\Common\Enums\Currency;
use App\Common\Helpers\ResponseHelper;
use App\Exceptions\AppException;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAndUpdateAccountService
{


    /**
     * Retrieve accounts for the authenticated user.
     *
     * @return mixed
     */
    public static function getAccount(): mixed
    {
        $userId = auth()->id();
        $accounts = Account::where('user_id', $userId)->get();
        return ResponseHelper::success($accounts);
    }

    public static function toggleEnabled(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $accountId = $request->input('account_id');
        $status = $request->input('enabled');

        if (empty($accountId)) {
            throw new AppException("Account ID must be provided.");
        }

        if (is_null($status)) {
            throw new AppException("Enabled status must be provided.");
        }

        $account = Account::where('user_id', $userId)
            ->where('account_id', $accountId)
            ->first();

        if (!$account) {
            throw new AppException("Account not found for the specified ID.");
        }

        $account->enabled = $status;
        if ($account->save()) {
            return ResponseHelper::success($account, "Account updated successfully.");
        }

        throw new AppException("Failed to update the account.");
    }


    /**
     * Retrieve account details for a specific currency.
     *
     * @param Request $request
     * @return Account
     */
    public static function getAccountDetails(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $accountId = $request->query('account_id');
        $currencyValue = $request->query('currency');

        if (empty($accountId) && empty($currencyValue)) {
            throw new AppException("At least one of 'account_id' or 'currency' must be provided.");
        }

        if (!empty($currencyValue)) {
            $currency = Currency::tryFrom($currencyValue);

            if (!$currency) {
                throw new AppException("Invalid Currency.");
            }
        }

        $query = Account::where('user_id', $userId);

        if (!empty($accountId)) {
            $query->where('account_id', $accountId);
        }

        if (!empty($currencyValue)) {
            $query->where('currency', $currency->name);
        }

        $account = $query->first();

        if (!$account) {
            throw new AppException("Account not found for the specified criteria.");
        }

        return ResponseHelper::success($account);
    }
}
