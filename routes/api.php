<?php

use App\Common\Enums\TokenAbility;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminRolePermissionController;
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
$adminAccessMiddleWare = array_merge($publicMiddleware, [
    "auth:sanctum",
    "ability:" . TokenAbility::ADMIN_ACCESS_API->value,
]);

// Public Routes (No Authentication Required)
Route::middleware($publicMiddleware)->group(function () {
    // Paystack Webhook
    Route::post("/paystack-whale-webhook", [MiscellaneousController::class, "handleCallbacks"]);

    // Authentication Endpoints
    Route::post("/sign-in", [AuthController::class, "signIn"]);
    Route::post("/initiate-registry", [AuthController::class, 'initializeRegistration']);
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
    Route::post("/complete-profile", [AuthController::class, 'completeProfile']);


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
            Route::patch("/update-in", [AccountController::class, "updateIn"]);

            Route::get("/", [AccountController::class, "getOrCreateAccountSettings"]);

            Route::post("/security-question", [AccountController::class, "createSecurityKey"]);
            Route::put("/security-question", [AccountController::class, "updateSecurityKey"]);

            Route::post("/next-of-kin", [AccountController::class, "addNextofKin"]);
            Route::put("/next-of-kin", [AccountController::class, "updateNextofKin"]);

            Route::put("/", [AccountController::class, "updateAccountSettings"]);
        });

        // update pin
        // update tag
    });
});


// Public Routes (No Authentication Required) | ADMIN
Route::middleware($publicMiddleware)->prefix("/vivian")->group(function () {
    // Authentication Endpoints
    Route::post("/sign-in", [AdminAuthController::class, "signIn"]);
    Route::post("/initiate-registry", [AdminAuthController::class, 'initializeRegistration']);
    Route::post("/send-otp", [AdminAuthController::class, "sendOtp"]);
    Route::post("/initiate-password-recovery", [AdminAuthController::class, "initiatePasswordRecovery"]);
    Route::post("/complete-password-recovery", [AdminAuthController::class, "completePasswordRecovery"]);
    Route::post("/verify-account", [AdminAuthController::class, "verifyAccount"]);
    Route::post("/change-password", [AdminAuthController::class, "changePassword"]);
});


// Token Issuer Routes (Special Permission Required) | ADMIN
Route::middleware($adminAccessMiddleWare)->prefix('/vivian')->group(function () {
    // Complete Profile
    Route::post("/complete-profile", [AdminAuthController::class, 'completeProfile']);


    // Dashboard Data
    Route::get('/dashboard/data', [AdminDashboardController::class, 'getDashboardData'])->name('admin.dashboard.data');

    // User Management
    Route::get('/users', [AdminUserController::class, 'getUsers'])->name('admin.users.list');
    Route::post('/users', [AdminUserController::class, 'createUser'])->name('admin.users.create');
    Route::put('/users/{userId}', [AdminUserController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/users/{userId}', [AdminUserController::class, 'deleteUser'])->name('admin.users.delete');

    // Account Management
    Route::get('/accounts', [AdminAccountController::class, 'getAccounts'])->name('admin.accounts.list');
    Route::post('/accounts', [AdminAccountController::class, 'createAccount'])->name('admin.accounts.create');
    Route::put('/accounts/{accountId}', [AdminAccountController::class, 'updateAccount'])->name('admin.accounts.update');
    Route::delete('/accounts/{accountId}', [AdminAccountController::class, 'deleteAccount'])->name('admin.accounts.delete');

    // Transaction Management
    Route::get('/transactions', [AdminTransactionController::class, 'getTransactions'])->name('admin.transactions.list');
    Route::post('/transactions', [AdminTransactionController::class, 'createTransaction'])->name('admin.transactions.create');
    Route::put('/transactions/{transactionId}', [AdminTransactionController::class, 'updateTransaction'])->name('admin.transactions.update');
    Route::delete('/transactions/{transactionId}', [AdminTransactionController::class, 'deleteTransaction'])->name('admin.transactions.delete');

    // Reports
    Route::get('/reports', [AdminReportController::class, 'getReports'])->name('admin.reports.list');
    Route::post('/reports', [AdminReportController::class, 'generateReport'])->name('admin.reports.generate');

    // Settings
    Route::get('/settings', [AdminSettingsController::class, 'getSettings'])->name('admin.settings.get');
    Route::put('/settings', [AdminSettingsController::class, 'updateSettings'])->name('admin.settings.update');

    // Roles and Permissions Management
    Route::get('/roles', [AdminRolePermissionController::class, 'getRolesList'])->name('admin.roles.list');
    Route::post('/roles', [AdminRolePermissionController::class, 'createRole'])->name('admin.roles.create');
    Route::put('/roles/{roleId}', [AdminRolePermissionController::class, 'updateRole'])->name('admin.roles.update');
    Route::delete('/roles/{roleId}', [AdminRolePermissionController::class, 'deleteRole'])->name('admin.roles.delete');

    Route::post('/roles/{roleId}/assign', [AdminRolePermissionController::class, 'assignRoleToUser'])->name('admin.roles.assign');
    Route::delete('/roles/{roleId}/remove', [AdminRolePermissionController::class, 'removeRoleFromUser'])->name('admin.roles.remove');

    Route::get('/permissions', [AdminRolePermissionController::class, 'getPermissionsList'])->name('admin.permissions.list');
    Route::post('/permissions', [AdminRolePermissionController::class, 'createPermission'])->name('admin.permissions.create');
    Route::put('/permissions/{permissionId}', [AdminRolePermissionController::class, 'updatePermission'])->name('admin.permissions.update');
    Route::delete('/permissions/{permissionId}', [AdminRolePermissionController::class, 'deletePermission'])->name('admin.permissions.delete');

    // Logs and Health
    Route::get('/logs', [AdminLogsController::class, 'getLogs'])->name('admin.logs.list');
    Route::get('/health', [AdminHealthController::class, 'getHealthStatus'])->name('admin.health.status');

    // Support Chat
    Route::post('/support/chat', [AdminSupportController::class, 'sendMessage'])->name('admin.support.chat');
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
    Artisan::call('migrate --force');
    return 'Migration Handled';
});