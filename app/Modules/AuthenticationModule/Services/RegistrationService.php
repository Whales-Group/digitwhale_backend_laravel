<?php

namespace App\Modules\AuthenticationModule\Services;

use App\Common\Helpers\CodeHelper;
use App\Common\Helpers\ResponseHelper;
use App\Models\User;
use App\Modules\MailModule\MailModuleMain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegistrationService
{
    /**
     * Initialize user registration process.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initializeRegistration(Request $request): JsonResponse
    {
        try {
            // Validate input for initialization
            $validator = Validator::make($request->all(), [
                "profile_type" => "required|string",
                "email" => "required|email|unique:users,email",
                "password" => "required|string|min:6",
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(
                    message: "Validation failed",
                    error: $validator->errors()->toArray()
                );
            }

            DB::beginTransaction();

            // Hash password and generate tag
            $hashedPassword = Hash::make($request->password);
            $tag = $this->generateTag($request->email);

            // Create user record
            $user = User::create([
                "profile_type" => $request->profile_type,
                "email" => $request->email,
                "tag" => $tag,
                "password" => $hashedPassword,
                "email_verified_at" => null,
            ]);

            // Send OTP for verification
            if (!$this->sendOtpToUser($user)) {
                DB::rollBack();
                return ResponseHelper::success(
                    message: "User registered successfully",
                    error: "Failed to send OTP Mail."
                );
            }

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
     * Complete user profile for an authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            // Validate input for profile completion
            $validator = Validator::make($request->all(), [
                "first_name" => "required|string",
                "last_name" => "required|string",
                "middle_name" => "nullable|string",
                "dob" => "required|string",
                "profile_url" => "nullable|string",
                "other_url" => "nullable|string",
                "phone_number" => "required|string|max:10",
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(
                    message: "Validation failed",
                    error: $validator->errors()->toArray()
                );
            }

            DB::beginTransaction();

            // Find the authenticated user
            $user = auth()->user(); // Assumes authentication middleware is active
            if (!$user) {
                return ResponseHelper::notFound("User not found");
            }

            // Update user profile
            $user->update([
                "first_name" => $request->first_name,
                "middle_name" => $request->middle_name,
                "last_name" => $request->last_name,
                "dob" => $request->dob,
                "profile_url" => $request->profile_url,
                "other_url" => $request->other_url,
                "phone_number" => $request->phone_number,
            ]);

            DB::commit();
            return ResponseHelper::success([], "Profile updated successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error(
                message: "An error occurred while updating profile",
                error: $e->getMessage()
            );
        }
    }

    /**
     * Send OTP to the user's email.
     *
     * @param User $user
     * @return bool
     */
    private function sendOtpToUser(User $user): bool
    {
        try {
            $otp = CodeHelper::generate(6);

            // Store OTP in the database
            DB::table("password_reset_tokens")->insert([
                "email" => $user->email,
                "token" => $otp,
                "created_at" => now()->addMinutes(5),
            ]);

            // Send OTP email
            $mailRequest = new Request([
                "email" => $user->email,
                "first_name" => $user->first_name ?? 'User',
                "otp" => $otp,
                "len_in_min" => 5,
            ]);

            return MailModuleMain::sendOtpMail($mailRequest);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Send OTP to an existing user's email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendOtp(Request $request): JsonResponse
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

            if (!$this->sendOtpToUser($user)) {
                return ResponseHelper::success(
                    message: "OTP sent successfully",
                    error: "Failed to send OTP Mail."
                );
            }

            return ResponseHelper::success([], "OTP sent successfully");

        } catch (\Exception $e) {
            return ResponseHelper::error(
                message: "An error occurred while sending OTP",
                error: $e->getMessage()
            );
        }
    }

    /**
     * Verify the user's account using OTP.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyAccount(Request $request): JsonResponse
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

        } catch (\Exception $e) {
            return ResponseHelper::error(
                message: "An error occurred during account verification",
                error: $e->getMessage()
            );
        }
    }

    /**
     * Generate a unique identifier (tag) for the user.
     *
     * @param string $email
     * @return string
     */
    private function generateTag(string $email): string
    {
        $localPart = explode('@', $email)[0];
        return '@' . $localPart . '_' . rand(100000, 999999);
    }
}