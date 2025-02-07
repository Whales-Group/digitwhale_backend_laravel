<?php

namespace App\Modules\AdminAuthenticationModule\Services;

use App\Common\Helpers\CodeHelper;
use App\Common\Helpers\ResponseHelper;
use App\Models\AdminUser; // Use the AdminUser model instead of AdminUser
use App\Models\User;
use App\Modules\MailModule\MailModuleMain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminRegistrationService
{
    /**
     * Initialize admin user registration process.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initializeAdminRegistration(Request $request): JsonResponse
    {
        try {
            // Validate input for initialization
            $validator = Validator::make($request->all(), [
                "profile_type" => "required|string",
                "email" => "required|email|unique:admin_users,email",
                "password" => "required|string|min:6",
                "role" => "required|string",
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(
                    message: "Validation failed",
                    error: $validator->errors()->toArray()
                );
            }

            if (User::where('email', $request->email)->exists()) {
                return ResponseHelper::error(
                    message: "Email already in use by Ordinary AdminUser."
                );
            }

            DB::beginTransaction();


            // Hash password and generate tag
            $hashedPassword = Hash::make($request->password);
            $tag = $this->generateTag($request->email);

            // Create admin user record
            $adminUser = AdminUser::create([
                "profile_type" => $request->profile_type,
                "role" => $request->role,
                "email" => $request->email,
                "tag" => $tag,
                "password" => $hashedPassword,
                "email_verified_at" => null,
                "permissions" => json_encode([]),
            ]);

            // Generate and send OTP
            if (!$this->sendOtpToAdmin($adminUser)) {
                DB::commit();
                return ResponseHelper::success(
                    message: "Admin registered successfully",
                    error: "Failed to send OTP Mail."
                );
            }

            DB::commit();
            return ResponseHelper::success([], "Admin registered successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error(
                message: "An error occurred during admin registration",
                error: $e->getMessage()
            );
        }
    }

    /**
     * Complete the profile of the authenticated admin user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAdminProfile(Request $request): JsonResponse
    {
        try {
            // Validate input for profile completion
            $validator = Validator::make($request->all(), [
                "first_name" => "required|string",
                "last_name" => "required|string",
                "middle_name" => "nullable|string",
                "dob" => "nullable|date", // Date of birth is optional
                "profile_url" => "nullable|string",
                "other_url" => "nullable|string",
                "phone_number" => "nullable|string|max:10",
                "permissions" => "nullable|array", // Permissions can be passed as an array
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(
                    message: "Validation failed",
                    error: $validator->errors()->toArray()
                );
            }

            DB::beginTransaction();

            // Fetch the authenticated admin user
            $adminUser = auth()->user();
            if (!$adminUser) {
                return ResponseHelper::notFound("Admin not found");
            }

            // Update admin user profile
            $adminUser->update([
                "first_name" => $request->first_name,
                "middle_name" => $request->middle_name,
                "last_name" => $request->last_name,
                "dob" => $request->dob,
                "profile_url" => $request->profile_url,
                "other_url" => $request->other_url,
                "phone_number" => $request->phone_number,
                "permissions" => json_encode($request->input("permissions", [])),
            ]);

            DB::commit();
            return ResponseHelper::success([], "Admin profile updated successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error(
                message: "An error occurred while updating admin profile",
                error: $e->getMessage()
            );
        }
    }

    /**
     * Send OTP to the admin's email.
     *
     * @param AdminUser $adminUser
     * @return bool
     */
    private function sendOtpToAdmin(AdminUser $adminUser): bool
    {
        try {
            $otp = CodeHelper::generate(6);

            // Store OTP in the database
            DB::table("password_reset_tokens")->insert([
                "email" => $adminUser->email,
                "token" => $otp,
                "created_at" => now()->addMinutes(5),
            ]);

            // Send OTP email
            $mailRequest = new Request([
                "email" => $adminUser->email,
                "first_name" => $adminUser->first_name ?? 'Admin',
                "otp" => $otp,
                "len_in_min" => 5,
            ]);

            return MailModuleMain::sendOtpMail($mailRequest);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Send OTP to an existing admin's email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendAdminOtp(Request $request): JsonResponse
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

            $adminUser = AdminUser::where("email", $request->email)->first();
            if (!$adminUser) {
                return ResponseHelper::notFound("Admin not found");
            }

            if (!$this->sendOtpToAdmin($adminUser)) {
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
     * Verify the admin's account using OTP.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyAdminAccount(Request $request): JsonResponse
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

            AdminUser::where("email", $request->email)->update([
                "email_verified_at" => now(),
            ]);

            DB::table("password_reset_tokens")
                ->where("email", $request->email)
                ->delete();

            return ResponseHelper::success([], "Admin account verified successfully");

        } catch (\Exception $e) {
            return ResponseHelper::error(
                message: "An error occurred during admin account verification",
                error: $e->getMessage()
            );
        }
    }

    /**
     * Generate a unique identifier (tag) for the admin.
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