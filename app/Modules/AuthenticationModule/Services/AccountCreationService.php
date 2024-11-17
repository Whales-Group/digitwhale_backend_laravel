<?php

namespace App\Modules\AuthenticationModule\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Common\Helpers\CodeHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Common\Helpers\ResponseHelper;
use Exception;
use App\Modules\MailModule\MailModuleMain;

class AccountCreationService
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "first_name" => "required|string",
                "last_name" => "required|string",
                "email" => "required|email|unique:users,email",
                "tag" => "required|string|unique:users,tag",
                "password" => "required|string",
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(
                    message: "Validation failed",
                    error: $validator->errors()->toArray()
                );
            }

            $hashedPassword = Hash::make($request->password);

            DB::beginTransaction();

            // Create user
            $user = User::create([
                "first_name" => $request->first_name,
                "last_name" => $request->last_name,
                "email" => $request->email,
                "tag" => $request->tag,
                "password" => $hashedPassword,
                // temp
                "email_verified_at" => now(),
            ]);

            // Create OTP record
            $otp = CodeHelper::generate(6);

            DB::table("password_reset_tokens")->insert([
                "email" => $user->email,
                "token" => $otp,
                "created_at" => now()->addMinutes(5),
            ]);

            // Send OTP email
            $mailRequest = new Request([
                "email" => $user->email,
                "first_name" => $user->first_name,
                "otp" => $otp,
                "len_in_min" => 5,
            ]);

            // $status = MailModuleMain::sendOtpMail($mailRequest);

            // if (!$status) {
            //     return ResponseHelper::success(
            //         message: "User registered successfully",
            //         error: "Failed to send OTP Mail."
            //     );
            // }

            DB::commit();
            return ResponseHelper::success([], "User registered successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error(
                message: "An error occurred during registration",
                error: $e->getMessage()
            );
        }
    }

    /**
     * Send OTP to the user's email
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "email" => "required|email",
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(
                    message: "Validation failed",
                    error: $validator->errors()->toArray()
                );
            }

            $user = User::where("email", $request->email)->first();

            if (!$user) {
                return ResponseHelper::notFound("User not found");
            }

            $otp = CodeHelper::generate(6);

            DB::table("password_reset_tokens")->insert([
                "email" => $user->email,
                "token" => $otp,
                "created_at" => now()->addMinutes(5),
            ]);

            $mailRequest = new Request([
                "email" => $user->email,
                "first_name" => $user->first_name,
                "otp" => $otp,
                "len_in_min" => 5,
            ]);

            $status = MailModuleMain::sendOtpMail($mailRequest);

            if ($status) {
                return ResponseHelper::success([], "OTP sent successfully");
            } else {
                return ResponseHelper::success(
                    message: "OTP sent successfully",
                    error: "Failed to send OTP Mail."
                );
            }
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: "An error occurred while sending OTP",
                error: $e->getMessage()
            );
        }
    }

    /**
     * Verify the user's account using OTP
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "email" => "required|email",
                "otp" => "required|string",
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(
                    message: "Validation failed",
                    error: $validator->errors()->toArray()
                );
            }

            $otpRecord = DB::table("password_reset_tokens")
                ->where("email", $request->email)
                ->where("token", $request->otp)
                ->first();

            if (!$otpRecord) {
                return ResponseHelper::error("Invalid OTP", 400);
            }

            User::where("email", $request->email)->update([
                "email_verified_at" => now(),
            ]);

            DB::table("password_reset_tokens")
                ->where("email", $request->email)
                ->delete();

            return ResponseHelper::success([], "Account verified successfully");
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: "An error occurred during account verification",
                error: $e->getMessage()
            );
        }
    }
}
