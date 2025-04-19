<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountSettingController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminRolePermissionController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\BillAndUtilsController;
use App\Http\Controllers\EncryptionController;
use App\Http\Controllers\MiscellaneousController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UtilsController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Middleware Definitions
$publicMiddleware = ['VerifyApiKey', 'SetStructure'];
$protectedMiddleware = array_merge($publicMiddleware, [
    'auth:sanctum',
    'ability:' . TokenAbility::ACCESS_API->value,
]);
$adminAccessMiddleWare = array_merge($publicMiddleware, [
    'auth:sanctum',
    'ability:' . TokenAbility::ADMIN_ACCESS_API->value,
]);

// Webhook Routes
Route::group(['prefix' => 'webhooks'], function () {
    Route::post('/paystack-whale', [MiscellaneousController::class, 'handlePaystackWebhook']);
    Route::post('/fincra-whale', [MiscellaneousController::class, 'handleFincraWebhook']);
    Route::post('/flutterwave-whale', [MiscellaneousController::class, 'handleFlutterwaveWebhook']);
});

// Authentication Routes
Route::group(['middleware' => $publicMiddleware], function () {
    // User Authentication
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/sign-in', [AuthController::class, 'signIn']);
        Route::post('/initiate-registry', [AuthController::class, 'initializeRegistration']);
        Route::post('/send-otp', [AuthController::class, 'sendOtp']);
        Route::post('/initiate-password-recovery', [AuthController::class, 'initiatePasswordRecovery']);
        Route::post('/complete-password-recovery', [AuthController::class, 'completePasswordRecovery']);
        Route::post('/verify-account', [AuthController::class, 'verifyAccount']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // Admin Authentication
    Route::group(['prefix' => 'vivian'], function () {
        Route::post('/sign-in', [AdminAuthController::class, 'signIn']);
        Route::post('/initiate-registry', [AdminAuthController::class, 'initializeRegistration']);
        Route::post('/send-otp', [AdminAuthController::class, 'sendOtp']);
        Route::post('/initiate-password-recovery', [AdminAuthController::class, 'initiatePasswordRecovery']);
        Route::post('/complete-password-recovery', [AdminAuthController::class, 'completePasswordRecovery']);
        Route::post('/verify-account', [AdminAuthController::class, 'verifyAccount']);
        Route::post('/change-password', [AdminAuthController::class, 'changePassword']);
    });
});

// Protected User Routes
Route::middleware($protectedMiddleware)->group(function () {
    // User Management
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/complete-profile', [AuthController::class, 'completeProfile']);
    Route::get('/user', [AuthController::class, 'getAuthenticatedUser']);

    Route::prefix('gpt')->group(function () {
        Route::post('/', [UtilsController::class, 'generatePaymentLink']);
        Route::get('/verify-payment/{reference}', [UtilsController::class, 'verifypayment']);
        Route::get('/tips', [UtilsController::class, 'getTips']);

        Route::prefix('c')->name('c')->group(function () {
            Route::post('/chat', [AiController::class, 'chat'])->name('chat');
            Route::post('/', [AiController::class, 'startConversation'])->name('start-conversation');
            Route::get('/', [AiController::class, 'getConversationHistory'])->name('conversation-history');
            Route::delete('/', [AiController::class, 'deleteConversation'])->name('delete-conversation');
            Route::put('/', [AiController::class, 'recoverConversation'])->name('recover-conversation');
            Route::get('/select-model', [AiController::class, 'selectModel'])->name('select-model');
        });
        Route::prefix('packages')->group(function () {
            Route::get('/', [UtilsController::class, 'getPackages']);
            Route::post('/subscribe/{packageType}', [UtilsController::class, 'subscribe']);
            Route::post('/unsubscribe', [UtilsController::class, 'unsubscribe']);
            Route::post('/upgrade/{newPackageType}', [UtilsController::class, 'upgrade']);
            Route::post('/downgrade/{newPackageType}', [UtilsController::class, 'downgrade']);
        });
    });

    // Account Management
    Route::prefix('accounts')->group(function () {
        Route::post('/', [AccountController::class, 'createAccount']);
        Route::get('/', [AccountController::class, 'getAccounts']);
        Route::get('/detail', [AccountController::class, 'getAccountDetails']);
        Route::put('/', [AccountController::class, 'updateAccount']);
        Route::delete('/', [AccountController::class, 'deleteAccount']);
        Route::get('/resolve', [AccountController::class, 'resolveAccount']);

        Route::prefix('settings')->group(function () {
            Route::put('/toggle-enabled', [AccountSettingController::class, 'toggleEnabled']);
            Route::get('/', [AccountSettingController::class, 'getOrCreateAccountSettings']);
            Route::put('/', [AccountSettingController::class, 'updateAccountSettings']);
        });

        Route::prefix('beneficiaries')->group(function () {
            Route::get('/', [BeneficiaryController::class, 'getAllBeneficiaries']);
            Route::post('/', [BeneficiaryController::class, 'createBeneficiary']);
            Route::put('/{beneficiary_id}', [BeneficiaryController::class, 'updateBeneficiary']);
            Route::delete('/{beneficiary_id}', [BeneficiaryController::class, 'deleteBeneficiary']);
            Route::post('/{beneficiary_id}/favorite', [BeneficiaryController::class, 'markAsFavorite']);
            Route::delete('/{beneficiary_id}/favorite', [BeneficiaryController::class, 'unmarkAsFavorite']);
            Route::get('/favorites', [BeneficiaryController::class, 'getFavoriteBeneficiaries']);
        });

        Route::prefix('transfer')->group(function () {
            Route::post('/{account_id}', [TransferController::class, 'transfer']);
            Route::put('/{account_id}', [TransferController::class, 'verifyTransferStatusBy']);
            Route::post('/', [TransferController::class, 'validateTransfer']);
        });

        Route::get('/transaction', [TransferController::class, 'getTransactions']);

        Route::prefix('verification')->group(function () {
            Route::put('/', [AccountController::class, 'addOrUpdateDocument']);
            Route::get('/', [AccountController::class, 'getUserDocuments']);
            Route::get('/required', [AccountController::class, 'getRequiredDocumentsByCountry']);
        });
    });

    // Core Banking Services
    Route::prefix('core')->group(function () {
        Route::post('/resolve-account/{account_id}', [TransferController::class, 'resolveAccount']);
        Route::get('/get-banks/{account_id}', [TransferController::class, 'getBanks']);
        Route::post('/resolve-internal-account', [TransferController::class, 'resolveAccountByIdentity']);
    });

    // Bills And Utilities Services
    Route::prefix('bills')->group(function () {
        Route::get('/', [BillAndUtilsController::class, "getBillCategories"]);
        Route::get('/{category}/billers', [BillAndUtilsController::class, "getBillerByCategory"]);
        Route::get('/{biller_code}/items', [BillAndUtilsController::class, "getBillerItems"]);
        Route::get('/{category_id}/billers/{biller_code}/validate', [BillAndUtilsController::class, "validateUserInformation"]);
        Route::post('/{biller_code}/billers/{item_code}', [BillAndUtilsController::class, "purchaseBill"]);
    });
});

// Admin Routes
Route::middleware($adminAccessMiddleWare)->prefix('vivian')->group(function () {
    Route::post('/complete-profile', [AdminAuthController::class, 'completeProfile']);
    Route::get('/dashboard/data', [AdminDashboardController::class, 'getDashboardData'])->name('admin.dashboard.data');

    Route::prefix('users')->group(function () {
        Route::get('/', [AdminUserController::class, 'getUsers'])->name('admin.users.list');
        Route::post('/', [AdminUserController::class, 'createUser'])->name('admin.users.create');
        Route::put('/{userId}', [AdminUserController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/{userId}', [AdminUserController::class, 'deleteUser'])->name('admin.users.delete');
    });

    Route::prefix('accounts')->group(function () {
        Route::get('/', [AdminAccountController::class, 'getAccounts'])->name('admin.accounts.list');
        Route::post('/', [AdminAccountController::class, 'createAccount'])->name('admin.accounts.create');
        Route::put('/{accountId}', [AdminAccountController::class, 'updateAccount'])->name('admin.accounts.update');
        Route::delete('/{accountId}', [AdminAccountController::class, 'deleteAccount'])->name('admin.accounts.delete');
    });

    Route::prefix('transactions')->group(function () {
        Route::get('/', [AdminTransactionController::class, 'getTransactions'])->name('admin.transactions.list');
        Route::post('/', [AdminTransactionController::class, 'createTransaction'])->name('admin.transactions.create');
        Route::put('/{transactionId}', [AdminTransactionController::class, 'updateTransaction'])->name('admin.transactions.update');
        Route::delete('/{transactionId}', [AdminTransactionController::class, 'deleteTransaction'])->name('admin.transactions.delete');
    });

    Route::prefix('roles')->group(function () {
        Route::get('/', [AdminRolePermissionController::class, 'getRolesList'])->name('admin.roles.list');
        Route::post('/', [AdminRolePermissionController::class, 'createRole'])->name('admin.roles.create');
        Route::put('/{roleId}', [AdminRolePermissionController::class, 'updateRole'])->name('admin.roles.update');
        Route::delete('/{roleId}', [AdminRolePermissionController::class, 'deleteRole'])->name('admin.roles.delete');
        Route::post('/{roleId}/assign', [AdminRolePermissionController::class, 'assignRoleToUser'])->name('admin.roles.assign');
        Route::delete('/{roleId}/remove', [AdminRolePermissionController::class, 'removeRoleFromUser'])->name('admin.roles.remove');
    });

    Route::prefix('permissions')->group(function () {
        Route::get('/', [AdminRolePermissionController::class, 'getPermissionsList'])->name('admin.permissions.list');
        Route::post('/', [AdminRolePermissionController::class, 'createPermission'])->name('admin.permissions.create');
        Route::put('/{permissionId}', [AdminRolePermissionController::class, 'updatePermission'])->name('admin.permissions.update');
        Route::delete('/{permissionId}', [AdminRolePermissionController::class, 'deletePermission'])->name('admin.permissions.delete');
    });

    Route::prefix('system')->group(function () {
        Route::prefix('reports')->group(function () {
            Route::get('/', [AdminReportController::class, 'getReports'])->name('admin.reports.list');
            Route::post('/', [AdminReportController::class, 'generateReport'])->name('admin.reports.generate');
        });

        Route::prefix('settings')->group(function () {
            Route::get('/', [AdminSettingsController::class, 'getSettings'])->name('admin.settings.get');
            Route::put('/', [AdminSettingsController::class, 'updateSettings'])->name('admin.settings.update');
        });

        Route::prefix('logs')->group(function () {
            Route::get('/', [AdminLogsController::class, 'getLogs'])->name('admin.logs.list');
        });

        Route::prefix('health')->group(function () {
            Route::get('/', [AdminHealthController::class, 'getHealthStatus'])->name('admin.health.status');
        });

        Route::prefix('support')->group(function () {
            Route::post('/chat', [AdminSupportController::class, 'sendMessage'])->name('admin.support.chat');
        });
    });
});

// Utility Routes
Route::group(['prefix' => 'utils'], function () {
    Route::post('/encrypt', [EncryptionController::class, 'encrypt']);
    Route::post('/decrypt', [EncryptionController::class, 'decrypt']);

    Route::get('/clear-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('config:cache');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return 'Cache cleared and config cached.';
    });

    Route::get('/migrate', function () {
        Artisan::call('migrate --force');
        return 'Migration Handled';
    });
});

