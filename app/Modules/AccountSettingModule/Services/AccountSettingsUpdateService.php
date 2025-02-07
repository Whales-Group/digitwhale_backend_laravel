<?php

namespace App\Modules\AccountSettingModule\Services;

use App\Common\Helpers\ResponseHelper;
use App\Models\Account;
use Exception;
use Illuminate\Http\Request;

class AccountSettingsUpdateService
{
    public function updateAccountSettings(Request $request)
    {
        try {
            $user = $request->auth()->user();

        } catch (Exception $e) {
            return ResponseHelper::unprocessableEntity(error: $e->getMessage());
        }
    }

    public function addSecurityQuestion(Request $request)
    {
        try {
            $user = $request->auth()->user();

        } catch (Exception $e) {
            return ResponseHelper::unprocessableEntity(error: $e->getMessage());
        }
    }

    public function addOrUpdateNextofKin(Request $request)
    {
        try {
            $user = $request->auth()->user();

        } catch (Exception $e) {
            return ResponseHelper::unprocessableEntity(error: $e->getMessage());
        }
    }


    public function updateTag(Request $request)
    {
        try {
            $tag = $request->input("tag");
            $user = $request->auth()->user();

            // upadate on users table
            $user->tag = $tag;
            $user->save();

            // update on account table
            $existingAza = Account::where("user_id", $user->id)
                ->exists();

            if ($existingAza) {
                Account::where("user_id", $user->id)
                    ->update(["tag" => $tag]);
            }

            return ResponseHelper::success(message: "Tag updated successfully");
        } catch (Exception $e) {
            return ResponseHelper::unprocessableEntity(error: $e->getMessage());
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $user = $request->auth()->user();

        } catch (Exception $e) {
            return ResponseHelper::unprocessableEntity(error: $e->getMessage());
        }
    }


    public function createOrUpdateTransactionPin(Request $request)
    {
        try {
            $user = $request->auth()->user();

        } catch (Exception $e) {
            return ResponseHelper::unprocessableEntity(error: $e->getMessage());
        }
    }

    public function toggleBalanceVisibility(Request $request)
    {
        try {
            $user = $request->auth()->user();

        } catch (Exception $e) {
            return ResponseHelper::unprocessableEntity(error: $e->getMessage());
        }
    }

    public function toggleBiometrics(Request $request)
    {
        try {
            $user = $request->auth()->user();

        } catch (Exception $e) {
            return ResponseHelper::unprocessableEntity(error: $e->getMessage());
        }
    }

    public function toggleAirTransfer(Request $request)
    {
        try {
            $user = $request->auth()->user();

        } catch (Exception $e) {
            return ResponseHelper::unprocessableEntity(error: $e->getMessage());
        }
    }
}