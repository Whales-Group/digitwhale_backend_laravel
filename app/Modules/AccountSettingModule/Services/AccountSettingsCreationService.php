<?php

namespace App\Modules\AccountSettingModule\Services;

use App\Common\Helpers\ResponseHelper;
use App\Models\AccountSetting;

class AccountSettingsCreationService
{
    /**
     * Get or create account settings for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrCreateAccountSettings()
    {
        $userId = request()->user()->id;

        $accountSettings = AccountSetting::where('user_id', $userId)->first();

        if (!$accountSettings) {
            $accountSettings = $this->createAccountSettings($userId);
        }
        return ResponseHelper::success($accountSettings, 'Account settings retrieved successfully.');
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