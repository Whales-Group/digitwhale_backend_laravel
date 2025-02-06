<?php

namespace App\Modules\AccountModule\Services;

use App\Common\Enums\Currency;
use App\Common\Helpers\ResponseHelper;
use App\Exceptions\AppException;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\AdminUser;
use App\Models\User;
use App\Models\VerificationRecord;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

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

    public static function updateAccount(Request $request): mixed
    {
        $userId = auth()->id();

        $accountId = $request->input('account_id');

        $updatableFields = [
            // 'phone_number',
            // 'tag',
            // 'blacklisted',
            'enabled',
            // 'intrest_rate',
            // 'max_balance',
            // 'daily_transaction_limit',
            // 'daily_transaction_count',
            // 'pnd',
        ];

        $updateData = $request->only($updatableFields);

        if (empty($accountId)) {
            throw new AppException("Account ID must be provided.");
        }

        if (empty($updateData)) {
            throw new AppException("No valid fields provided for update.");
        }

        $account = Account::where('user_id', $userId)
            ->where('account_id', $accountId)
            ->first();

        if (!$account) {
            throw new AppException("Account not found for the specified ID.");
        }

        if ($account->updateAccount($updateData)) {
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

    public function updateIn(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $key = $request->input("key");
            $value = $request->input("value");
            $accessor = $request->input("accessor");

            $updatableAccessors = [
                'users',
                'accounts',
                'account_settings',
                'verification_records'
            ];

            $exemptions = [
                'user_id',
                'email',
                'account_id',
                'balance',
                'account_type',
                'currency',
                'validated_name',
                'blacklisted',
                'intrest_rate',
                'max_balance',
                'daily_transaction_limit',
                'daily_transaction_count',
                'pnd',
                'dedicated_account_id',
                'account_number',
                'customer_id',
                'customer_code',
                'service_provider',
                'service_bank',
                "first_name",
                "last_name",
                "password",
                "email_verified_at",
                "dob",
                "profile_type"
            ];

            if (empty($userId)) {
                throw new AppException("User is not authenticated");
            }

            if (empty($key)) {
                throw new AppException("Key cannot be empty");
            }

            if (empty($value)) {
                throw new AppException("Value cannot be empty");
            }

            if ($accessor === '*') {
                $accessors = $updatableAccessors;
            } elseif (in_array($accessor, $updatableAccessors)) {
                $accessors = [$accessor];
            } else {
                throw new AppException("Invalid accessor value");
            }

            if (in_array($key, $exemptions)) {
                throw new AppException("Key is not updatable");
            }


            foreach ($accessors as $accessor) {
                switch ($accessor) {
                    case 'users':
                        $this->updateInUser($userId, $key, $value);
                        break;
                    case 'accounts':
                        $this->updateInAccount($userId, $key, $value);
                        break;
                    case 'account_settings':
                        $this->updateInAccountSettings($userId, $key, $value);
                        break;
                    case 'verification_records':
                        $this->updateInVerificationRecord($userId, $key, $value);
                        break;
                }
            }

            return ResponseHelper::success(message: "Updated successfully.", );
        } catch (Exception $e) {
            throw new AppException($e->getMessage());
        }
    }

    private function updateInUser(string $userId, string $key, string $value): void
    {
        $user = User::where('id', $userId)->first();
        if ($user) {
            $user->{$key} = $value;
            $user->save();
        } else {
            Log::error("User not found");
        }
    }

    private function updateInAccount(string $userId, string $key, string $value): void
    {
        $account = Account::where('user_id', $userId)->first();
        if ($account) {
            $account->{$key} = $value;
            $account->save();
        } else {
            Log::error("Account not found");
        }
    }

    private function updateInAccountSettings(string $userId, string $key, string $value): void
    {
        $accountSetting = AccountSetting::where('user_id', $userId)->first();
        if ($accountSetting) {
            $accountSetting->{$key} = $value;
            $accountSetting->save();
        } else {
            Log::error("Account setting not found");
        }
    }

    private function updateInVerificationRecord(string $userId, string $key, string $value): void
    {
        $accountSetting = AccountSetting::where('user_id', $userId)->first();

        $record = VerificationRecord::where('account_setting_id', $accountSetting->id)->first();
        if ($record) {
            $record->{$key} = $value;
            $record->save();
        } else {
            Log::error("Verification record not found");
        }
    }


}
