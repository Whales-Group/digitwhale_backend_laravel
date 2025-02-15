<?php

use App\Common\Enums\TokenAbility;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountSettingController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminRolePermissionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MiscellaneousController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Middleware Groups
$publicMiddleware = ["VerifyApiKey", "SetStructure"];
$protectedMiddleware = array_merge($publicMiddleware, [
    "auth:sanctum",
    "ability:" . TokenAbility::ACCESS_API->value,
    // "Bt"
]);
$adminAccessMiddleWare = array_merge($publicMiddleware, [
    "auth:sanctum",
    "ability:" . TokenAbility::ADMIN_ACCESS_API->value,
]);

// WEBHOOKS
Route::post("/paystack-whale-webhook", [MiscellaneousController::class, "handlePaystackWebhook"]);
Route::post("/fincra-whale-webhook", [MiscellaneousController::class, "handleFincraWebhook"]);


// Public Routes (No Authentication Required)
Route::middleware($publicMiddleware)->group(function () {
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

    Route::get("/logout", [AuthController::class, "logout"]);
    Route::post("/complete-profile", [AuthController::class, 'completeProfile']);
    Route::get("/user", [AuthController::class, 'getAuthenticatedUser']);

    Route::prefix("/accounts")->group(function () {

        Route::post("/", [AccountController::class, "createAccount"]);
        Route::get("/", [AccountController::class, "getAccounts"]);
        Route::get("/detail", [AccountController::class, 'getAccountDetails']);
        Route::put("/", [AccountController::class, "updateAccount"]);
        Route::delete("/", [AccountController::class, "deleteAccount"]);
        Route::get("/resolve", [AccountController::class, "resolveAccount"]);

        Route::prefix("/settings")->group(function () {

            Route::put("/toggle-enabled", [AccountSettingController::class, "toggleEnabled"]);
            Route::get("/", [AccountSettingController::class, "getOrCreateAccountSettings"]);
            Route::put("/", [AccountSettingController::class, "updateAccountSettings"]);

        });

        Route::prefix("/transfer")->group(function () {
            Route::post("/{account_id}", [TransferController::class, "transfer"]);
            Route::put("/{account_id}", [TransferController::class, "verifyTransferStatusBy"]);
        });

        Route::get("/transaction", [TransferController::class, "getTransactions"]);


    });

    Route::prefix("/core")->group(function () {
        Route::post("/resolve-account/{account_id}", [TransferController::class, "resolveAccount"]);
        Route::get("/get-banks/{account_id}", [TransferController::class, "getBanks"]);
        Route::post("/resolve-internal-account", [TransferController::class, "resolveAccountByIdentity"]);
    });

});

// Public Routes (No Authentication Required) | ADMIN
Route::middleware($publicMiddleware)->prefix("/vivian")->group(function () {
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
    Route::post("/complete-profile", [AdminAuthController::class, 'completeProfile']);
    Route::get('/dashboard/data', [AdminDashboardController::class, 'getDashboardData'])->name('admin.dashboard.data');
    Route::prefix('/users')->group(function () {
        Route::get('/', [AdminUserController::class, 'getUsers'])->name('admin.users.list');
        Route::post('/', [AdminUserController::class, 'createUser'])->name('admin.users.create');
        Route::put('/{userId}', [AdminUserController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/{userId}', [AdminUserController::class, 'deleteUser'])->name('admin.users.delete');
    });
    Route::prefix('/accounts')->group(function () {
        Route::get('/', [AdminAccountController::class, 'getAccounts'])->name('admin.accounts.list');
        Route::post('/', [AdminAccountController::class, 'createAccount'])->name('admin.accounts.create');
        Route::put('/{accountId}', [AdminAccountController::class, 'updateAccount'])->name('admin.accounts.update');
        Route::delete('/{accountId}', [AdminAccountController::class, 'deleteAccount'])->name('admin.accounts.delete');
    });
    Route::prefix('/transactions')->group(function () {
        Route::get('/', [AdminTransactionController::class, 'getTransactions'])->name('admin.transactions.list');
        Route::post('/', [AdminTransactionController::class, 'createTransaction'])->name('admin.transactions.create');
        Route::put('/{transactionId}', [AdminTransactionController::class, 'updateTransaction'])->name('admin.transactions.update');
        Route::delete('/{transactionId}', [AdminTransactionController::class, 'deleteTransaction'])->name('admin.transactions.delete');
    });
    Route::prefix('/reports')->group(function () {
        Route::get('/', [AdminReportController::class, 'getReports'])->name('admin.reports.list');
        Route::post('/', [AdminReportController::class, 'generateReport'])->name('admin.reports.generate');
    });
    Route::prefix('/settings')->group(function () {
        Route::get('/', [AdminSettingsController::class, 'getSettings'])->name('admin.settings.get');
        Route::put('/', [AdminSettingsController::class, 'updateSettings'])->name('admin.settings.update');
    });
    Route::prefix('/roles')->group(function () {
        Route::get('/', [AdminRolePermissionController::class, 'getRolesList'])->name('admin.roles.list');
        Route::post('/', [AdminRolePermissionController::class, 'createRole'])->name('admin.roles.create');
        Route::put('/{roleId}', [AdminRolePermissionController::class, 'updateRole'])->name('admin.roles.update');
        Route::delete('/{roleId}', [AdminRolePermissionController::class, 'deleteRole'])->name('admin.roles.delete');
        Route::post('/{roleId}/assign', [AdminRolePermissionController::class, 'assignRoleToUser'])->name('admin.roles.assign');
        Route::delete('/{roleId}/remove', [AdminRolePermissionController::class, 'removeRoleFromUser'])->name('admin.roles.remove');
    });
    Route::prefix('/permissions')->group(function () {
        Route::get('/', [AdminRolePermissionController::class, 'getPermissionsList'])->name('admin.permissions.list');
        Route::post('/', [AdminRolePermissionController::class, 'createPermission'])->name('admin.permissions.create');
        Route::put('/{permissionId}', [AdminRolePermissionController::class, 'updatePermission'])->name('admin.permissions.update');
        Route::delete('/{permissionId}', [AdminRolePermissionController::class, 'deletePermission'])->name('admin.permissions.delete');
    });
    Route::prefix('/logs')->group(function () {
        Route::get('/', [AdminLogsController::class, 'getLogs'])->name('admin.logs.list');
    });
    Route::prefix('/health')->group(function () {
        Route::get('/', [AdminHealthController::class, 'getHealthStatus'])->name('admin.health.status');
    });
    Route::prefix('/support')->group(function () {
        Route::post('/chat', [AdminSupportController::class, 'sendMessage'])->name('admin.support.chat');
    });
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