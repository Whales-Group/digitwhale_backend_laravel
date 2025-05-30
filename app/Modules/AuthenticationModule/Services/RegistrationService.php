<?php

namespace App\Modules\AuthenticationModule\Services;

use App\Helpers\CodeHelper;
use App\Helpers\ResponseHelper;
use App\Exceptions\AppException;
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
                "business_name" => "nullable|string",
                "country" => "required|string",
                "email" => "required|email|unique:users,email",
                "password" => "required|string|min:6",
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(
                    message: "Validation failed",
                    error: $validator->errors()->toArray()
                );
            }

            if ($request->profile_type == 'corporate' && !$request->business_name) {
                return ResponseHelper::error(
                    message: "Business name is required for corporate accounts",
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
                "country_iso" => $request->country,
                'business_name' => $request->business_name,
                "password" => $hashedPassword,
                "email_verified_at" => null,
            ]);

            DB::commit();

            // Send OTP for verification
            if (!$this->sendOtpToUser($user)) {
                return ResponseHelper::success(
                    message: "User registered successfully",
                    error: "Failed to send OTP Mail."
                );
            }

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
                "first_name" => "sometimes",
                "last_name" => "sometimes",
                "middle_name" => "sometimes",
                "date_of_birth" => "sometimes",
                "profile_url" => "sometimes",
                "other_url" => "sometimes",
                "phone_number" => "sometimes",
                "gender" => "sometimes",
                "nin" => "sometimes",
                "bvn" => "sometimes",
                "marital_status" => "sometimes",
                "employment_status" => "sometimes",
                "annual_income" => "sometimes",
                "country_iso" => "sometimes",
                "state_province" => "sometimes",
                "city" => "sometimes",
                "street_address" => "sometimes",
                "street_number" => "sometimes",
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
                "date_of_birth" => $request->date_of_birth,
                "profile_url" => $request->profile_url,
                "other_url" => $request->other_url,
                "phone_number" => $request->phone_number,
                "gender" => $request->gender,
                "nin" => $request->nin,
                "bvn" => $request->bvn,
                "marital_status" => $request->marital_status,
                "employment_status" => $request->employment_status,
                "annual_income" => $request->annual_income,
                "country_iso" => $request->country_iso,
                "state_province" => $request->state_province,
                "city" => $request->city,
                "street_address" => $request->street_address,
                "street_number" => $request->street_number,
            ]);

            DB::commit();
            return ResponseHelper::success($user, "Profile updated successfully");

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

    /**
     * Get the authenticated user's details.
     *
     * @return JsonResponse
     */
    public function getAuthenticatedUser(): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return ResponseHelper::notFound("User not found");
            }

            return ResponseHelper::success($user, "User details retrieved successfully");

        } catch (\Exception $e) {
            return ResponseHelper::error(
                message: "An error occurred while retrieving user details",
                error: $e->getMessage()
            );
        }
    }
}