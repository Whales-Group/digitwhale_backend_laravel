<?php

namespace App\Modules\AccountSettingModule\Services;

use App\Common\Enums\Status;
use App\Common\Enums\VerificationType;
use App\Common\Helpers\ResponseHelper;
use App\Models\AccountSetting;
use App\Models\NextOfKin;
use App\Models\SecurityQuestion;
use App\Models\VerificationRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountSettingsCreationService
{

    public function getOrCreateAccountSettings(Request $request)
    {
        $userId = $request->user()->id;

        $accountSettings = AccountSetting::where('user_id', $userId)->first();

        if (!$accountSettings) {
            try {
                DB::beginTransaction();

                $newAccountSettings = $this->buildAccountSettings($request);

                $newAccountSettings->save();

                $this->createVerificationRecords($newAccountSettings);

                DB::commit();

                return ResponseHelper::success($newAccountSettings
                ->with('verifications')
                ->firstOrFail());
            } catch (\Exception $e) {
                DB::rollBack();

                return ResponseHelper::error(
                    message: "An error occurred during account settings creation",
                    error: $e->getMessage()
                );
            }
        }

        return ResponseHelper::success($accountSettings
        ->with('verifications')
        ->firstOrFail());

    }

    private function buildAccountSettings(Request $request)
    {
        $user = $request->user();

        $newAccountSettings = new AccountSetting([
            'user_id' => $user->id,
            'hide_balance' => false,
            'enable_biometrics' => false,
            'enable_air_transfer' => false,
            'enable_notifications' => true,
            'address' => null,
            'transaction_pin' => null,
            'enabled_2fa' => false,
            'fcm_tokens' => [],
        ]);

        return $newAccountSettings;
    }
    private function createVerificationRecords(AccountSetting $accountSettings)
    {
        $records = VerificationType::cases();

        foreach ($records as $record) {
            VerificationRecord::create([
                'account_setting_id' => $accountSettings->id,
                'type' => $record->value,
                'status' => Status::NONE,
                'value' => '',
                'url' => '',
            ]);
        }
    }
}