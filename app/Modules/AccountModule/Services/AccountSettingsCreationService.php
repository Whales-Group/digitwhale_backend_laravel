<?php

namespace App\Modules\AccountModule\Services;

use App\Common\Enums\Status;
use App\Common\Enums\VerificationType;
use App\Common\Helpers\ResponseHelper;
use App\Models\AccountSetting;
use App\Models\NextOfKin;
use App\Models\PersonalDetails;
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

                $this->createPersonalDetails($newAccountSettings, $request->user());

                DB::commit();

                return ResponseHelper::success($newAccountSettings
                ->with('verifications')
                ->with('personalDetails')
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
        ->with('personalDetails')
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

    private function createPersonalDetails(AccountSetting $accountSettings, $user)
    {
        PersonalDetails::create([
            'account_setting_id' => $accountSettings->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'middle_name' => $user->middle_name,
            'tag' => $user->tag,
            'date_of_birth' => $user->date_of_birth,
            'gender' => null,
            'phone_number' => $user->phone_number ?? null,
            'email' => $user->email,
            'nin' => null,
            'bvn' => null,
            'marital_status' => null,
            'employment_status' => null,
            'annual_income' => null,
        ]);
    }
}