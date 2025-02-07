<?php

namespace App\Modules\AdminAuthenticationModule\Services;

use App\Common\Enums\TokenAbility;
use App\Common\Helpers\ResponseHelper;
use App\Models\AdminUser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SignInService
{
    /**
     * Handle user login.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                "identifier" => "required|string",
                "password" => "required",
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(
                    "Identifier or password missing.",
                    401,
                    $validator->errors()->toArray()
                );
            }

            // Determine if the identifier is an email or tag
            $identifier = $request->identifier;
            $user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
                ? AdminUser::where("email", $identifier)->first()
                : AdminUser::where("tag", $identifier)->first();

            if (!$user) {
                return ResponseHelper::error(
                    "Invalid identifier.",
                    401
                );
            }

            if (!$user || !Hash::check($request->password, $user->password)) {
                return ResponseHelper::error(
                    "Invalid password.",
                    401
                );
            }

            if (!$user->email_verified_at) {
                return ResponseHelper::error("Email not verified.", 401);
            }

            // Create and return token
            $token = $user->createToken(TokenAbility::ADMIN_ACCESS_API->value)->plainTextToken;

            return ResponseHelper::success(
                data: ["token" => $token, "user" => $user],
                message: "Login successful"
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                "An error occurred during login: " . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Handle user logout.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ResponseHelper::error("AdminUser not authenticated.", 401);
            }

            // Revoke all tokens for the user
            $user->tokens()->delete();

            return ResponseHelper::success([], "Logout successful.", 200);
        } catch (Exception $e) {
            return ResponseHelper::error(
                "An error occurred during logout: " . $e->getMessage(),
                500
            );
        }
    }
}
