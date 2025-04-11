<?php

namespace App\Modules\AuthenticationModule\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\PasswordRecoveryToken;
use Exception;

class AccountRecoveryService
{
    public function initiatePasswordRecovery(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "email" => "required|email",
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" => "Email is required for password recovery.",
                    ],
                    400
                );
            }

            $user = User::where("email", $request->email)->first();

            if (!$user) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" => "User not found.",
                    ],
                    404
                );
            }

            $recoveryToken = rand(100000, 999999);

            DB::table('personal_access_tokens')->updateOrCreate(
                ["user_id" => $user->id],
                [
                    "token" => $recoveryToken,
                    "expires_at" => now()->addMinutes(5),
                ]
            );

            Mail::to($request->email)->send(
                new \App\Mail\OtpMail(['otp' => $recoveryToken, 'name' => $user->first_name ?? $user->email])
            );

            return response()->json(
                [
                    "status" => "success",
                    "message" =>
                        "Password recovery initiated. Check your email for further instructions.",
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    "status" => "error",
                    "message" =>
                        "An error occurred during password recovery initiation: " .
                        $e->getMessage(),
                ],
                500
            );
        }
    }

    public function completePasswordRecovery(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "email" => "required|email",
                "recovery_token" => "required|numeric", // Added numeric validation for recovery_token
                "new_password" => "required|string|min:8", // Added minimum length requirement for new password
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" =>
                            "Email, recovery token, or new password is missing.",
                    ],
                    400
                );
            }

            $user = User::where("email", $request->email)->first();

            if (!$user) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" => "User not found.",
                    ],
                    404
                );
            }

            $passwordRecoveryToken = DB::table('personal_access_tokens')->where(
                "user_id",
                $user->id
            )
                ->where("token", $request->recovery_token)
                ->first();

            if (
                !$passwordRecoveryToken ||
                $passwordRecoveryToken->expires_at < now()
            ) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" => "Invalid or expired recovery token.",
                    ],
                    401
                );
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            $passwordRecoveryToken->delete();

            return response()->json(
                [
                    "status" => "success",
                    "message" => "Password updated successfully",
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    "status" => "error",
                    "message" =>
                        "An error occurred while completing password recovery: " .
                        $e->getMessage(),
                ],
                500
            );
        }
    }
}
