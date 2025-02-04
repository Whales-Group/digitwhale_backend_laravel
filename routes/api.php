<?php

use App\Common\Enums\TokenAbility;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MiscellaneousController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Middleware Groups
$publicMiddleware = ["VerifyApiKey", "SetStructure"];
$protectedMiddleware = array_merge($publicMiddleware, [
    "auth:sanctum",
    "ability:" . TokenAbility::ACCESS_API->value,
]);
$tokenIssuerMiddleware = array_merge($publicMiddleware, [
    "auth:sanctum",
    "ability:" . TokenAbility::ISSUE_ACCESS_TOKEN->value,
]);

// Public Routes (No Authentication Required)
Route::middleware($publicMiddleware)->group(function () {
    // Paystack Webhook
    Route::post("/paystack-whale-webhook", [MiscellaneousController::class, "handleCallbacks"]);

    // Authentication Endpoints
    Route::post("/sign-in", [AuthController::class, "signIn"]);
    Route::post("/register", [AuthController::class, "register"]);
    Route::post("/send-otp", [AuthController::class, "sendOtp"]);
    Route::post("/initiate-password-recovery", [AuthController::class, "initiatePasswordRecovery"]);
    Route::post("/complete-password-recovery", [AuthController::class, "completePasswordRecovery"]);
    Route::post("/verify-account", [AuthController::class, "verifyAccount"]);
    Route::post("/change-password", [AuthController::class, "changePassword"]);
});

// Protected Routes (Authentication Required)
Route::middleware($protectedMiddleware)->group(function () {
    // Logout Endpoint
    Route::get("/logout", [AuthController::class, "logout"]);

    // Account Management Endpoints
    Route::prefix("/accounts")->group(function () {
        Route::post("/", [AccountController::class, "createAccount"]);
        Route::get("/", [AccountController::class, "getAccounts"]);
        Route::get("/detail", [AccountController::class, 'getAccountDetails']);
        Route::put("/", [AccountController::class, "updateAccount"]);
        Route::delete("/", [AccountController::class, "deleteAccount"]);

        // Resolve Account
        Route::get("/resolve", [AccountController::class, "resolveAccount"]);

        // Account Settings
        Route::prefix("/settings")->group(function () {
            Route::get("/", [AccountController::class, "getOrCreateAccountSettings"]);

            Route::post("/security-question", [AccountController::class, "createSecurityKey"]);
            Route::put("/security-question", [AccountController::class, "updateSecurityKey"]);

            Route::post("/next-of-kin", [AccountController::class, "addNextofKin"]);
            Route::put("/next-of-kin", [AccountController::class, "updateNextofKin"]);
            
            Route::put("/", [AccountController::class, "updateAccountSettings"]);
        });
    });
});

// Token Issuer Routes (Special Permission Required)
Route::middleware($tokenIssuerMiddleware)->group(function () {
});

// Cache Clearing Endpoint (For Debugging/Development)
Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('route:clear');
    return 'Cache cleared and config cached.';
});

// Cache Clearing Endpoint (For Debugging/Development)
Route::get('/migrate', function () {
    Artisan::call('migrate');
    return 'Migration Handled';
});