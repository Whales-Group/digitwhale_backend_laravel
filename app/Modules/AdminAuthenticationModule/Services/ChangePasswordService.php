<?php

namespace App\Modules\AdminAuthenticationModule\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\AdminUser;
use Exception;

class ChangePasswordService
{
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "email" => "required|email",
                "old_password" => "required|string",
                "new_password" => "required|string|min:8",
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" =>
                            "Email, old password, or new password missing.",
                    ],
                    400
                );
            }

            $user = AdminUser::where("email", $request->email)->first();

            if (
                !$user ||
                !Hash::check($request->old_password, $user->password)
            ) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" => "Invalid email or old password.",
                    ],
                    401
                );
            }

            // Update the user's password
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json(
                [
                    "status" => "success",
                    "message" => "Password changed successfully",
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    "status" => "error",
                    "message" =>
                        "An error occurred while changing password: " .
                        $e->getMessage(),
                ],
                500
            );
        }
    }
}
