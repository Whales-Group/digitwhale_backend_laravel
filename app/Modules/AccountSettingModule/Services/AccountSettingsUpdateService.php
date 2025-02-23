<?php

namespace App\Modules\AccountSettingModule\Services;

use App\Helpers\ResponseHelper;
use App\Models\AccountSetting;
use App\Models\NextOfKin;
use App\Models\SecurityQuestion;
use App\Models\VerificationRecord;
use Exception;

class AccountSettingsUpdateService
{
    public function updateAccountSettings()
    {
        try {
            $user = auth()->user();

            $accountSettings = AccountSetting::where('user_id', $user->id)->first();

            if (!$accountSettings) {
                $accountSettings = $this->createAccountSettings($user->id);
            }

            $accountSettings = $this->updateAccountSetting($accountSettings);

            return ResponseHelper::success(data: $accountSettings, message: 'Account settings updated successfully');
        } catch (Exception $e) {
            return ResponseHelper::unprocessableEntity(error: $e->getMessage());
        }
    }

    private function updateAccountSetting($accountSetting)
    {
        $accountSetting->update([
            'hide_balance' => request()->input('hide_balance'),
            'enable_biometrics' => request()->input('enable_biometrics'),
            'enable_air_transfer' => request()->input('enable_air_transfer'),
            'enable_notifications' => request()->input('enable_notifications'),
            'transaction_pin' => request()->input('transaction_pin'),
            'enabled_2fa' => request()->input('enabled_2fa'),
        ]);

        return $accountSetting;
    }

    /**
     * Create new account settings for the user.
     *
     * @param int $userId
     * @return AccountSetting
     */
    private function createAccountSettings(int $userId): AccountSetting
    {
        $accountSettings = new AccountSetting([
            'user_id' => $userId,
            'hide_balance' => false,
            'enable_biometrics' => false,
            'enable_air_transfer' => false,
            'enable_notifications' => true,
            'transaction_pin' => null,
            'enabled_2fa' => false,
        ]);

        $accountSettings->save();

        return $accountSettings;
    }
}