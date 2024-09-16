<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaystackController;

use Illuminate\Support\Facades\Route;
use App\Common\Enums\TokenAbility;

Route::middleware(["VerifyApiKey", "SetStructure"])->group(function () {
    Route::post("/paystack-whale-webhook", [
        PaystackController::class,
        "handleCallbacks",
    ]);

    Route::post("/sign-in", [AuthController::class, "signIn"]);

    Route::post("/register", [AuthController::class, "register"]);
    Route::post("/send-otp", [AuthController::class, "sendOtp"]);
    Route::post("/initiate-password-recovery", [
        AuthController::class,
        "initiatePasswordRecovery",
    ]);
    Route::post("/complete-password-recovery", [
        AuthController::class,
        "completePasswordRecovery",
    ]);
    Route::post("/verify-account", [AuthController::class, "verifyAccount"]);
    Route::post("/change-password", [AuthController::class, "changePassword"]);
});

Route::middleware([
    "VerifyApiKey",
    "SetStructure",
    "auth:sanctum",
    "ability:" . TokenAbility::ACCESS_API->value,
])->group(function () {
    Route::post("/logout", [AuthController::class, "logout"]);
});

Route::middleware([
    "VerifyApiKey",
    "SetStructure",
    "auth:sanctum",
    "ability:" . TokenAbility::ISSUE_ACCESS_TOKEN->value,
])->group(function () {});
