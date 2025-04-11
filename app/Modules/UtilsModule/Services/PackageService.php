<?php

namespace App\Modules\UtilsModule\Services;

use App\Enums\TransferType;
use App\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\Package;
use App\Models\Subscription;
use App\Modules\TransferModule\Services\TransactionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PackageService
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Perform security checks on user's account before package operations
     * @param string|null $packageType Optional package type for balance check
     */
    private function performSecurityChecks(?string $packageType = null): Account
    {
        $user = Auth::user();
        if (!$user) {
            throw new Exception('User not authenticated', 401);
        }

        $account = Account::where('user_id', $user->id)->first();
        if (!$account) {
            throw new Exception('No account found for this user', 404);
        }

        if (!$account->enabled) {
            throw new Exception('[Account Disabled] - Cannot perform package operations. Contact customer support', 403);
        }

        if ($account->blacklisted) {
            throw new Exception('[Account Blacklisted] - Cannot perform package operations. MESSAGE: ' . $account->blacklist_text, 403);
        }

        if ($account->pnd) {
            throw new Exception('Account cannot perform operations at the moment due to PND status. Contact customer support', 403);
        }

        if ($packageType) {
            $package = Package::where('type', $packageType)->first();
            if (!$package) {
                throw new Exception('Package not found', 404);
            }

            $packagePrice = (float) str_replace('/month', '', $package->price);
            if ($packagePrice > 0 && $account->balance < $packagePrice) {
                throw new Exception('Insufficient funds. Please top up your account balance.', 400);
            }

            if ($packagePrice < 0) {
                throw new Exception('Invalid package price', 400);
            }
        }

        return $account;
    }

    /**
     * Get all available packages
     */
    public function getPackages(): JsonResponse
    {
        try {
            $packages = Package::with('categories.features')->get();
            return ResponseHelper::success($packages);
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Subscribe user to a package
     */
    public function subscribe(string $packageType): JsonResponse
    {
        try {
            $user = Auth::user();
            $lock = Cache::lock("subscribe_{$user->id}", 10); // Lock for 10 seconds

            if (!$lock->get()) {
                throw new Exception('Another subscription operation is in progress. Please try again.', 429);
            }

            try {
                $account = $this->performSecurityChecks($packageType);
                $package = Package::where('type', $packageType)->first();

                return DB::transaction(function () use ($user, $account, $package) {
                    $activeSubscription = $user->subscriptions()
                        ->where('is_active', true)
                        ->first();

                    if ($activeSubscription) {
                        throw new Exception('User already has an active subscription. Please unsubscribe first.', 400);
                    }

                    $subscription = Subscription::create([
                        'user_id' => $user->id,
                        'package_id' => $package->id,
                        'start_date' => Carbon::now(),
                        'end_date' => null,
                        'is_active' => true,
                    ]);

                    if ($package->price !== '0/month') {
                        $packagePrice = (float) str_replace('/month', '', $package->price);
                        $transactionData = [
                            'currency' => $account->currency ?? 'NGN',
                            'to_sys_account_id' => null,
                            'to_user_name' => 'WhaleGPT System',
                            'to_user_email' => 'system@whalegpt.com',
                            'to_bank_name' => "WhaleGPT System",
                            'to_bank_code' => "00",
                            'to_account_number' => null,
                            'transaction_reference' => 'SUB_' . $subscription->id . '_' . time(),
                            'status' => 'completed',
                            'type' => 'subscription',
                            'amount' => $packagePrice,
                            'note' => "Subscription to {$package->type} package",
                        ];
                        $this->transactionService->registerTransaction($transactionData, TransferType::WHALE_TO_WHALE);
                    }

                    return ResponseHelper::success([
                        'message' => "Successfully subscribed to {$package->type} package",
                        'subscription' => $subscription->load('package'),
                    ]);
                });
            } finally {
                $lock->release();
            }
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Unsubscribe user from their current package
     */
    public function unsubscribe(): JsonResponse
    {
        try {
            $user = Auth::user();
            $lock = Cache::lock("unsubscribe_{$user->id}", 10);

            if (!$lock->get()) {
                throw new Exception('Another unsubscribe operation is in progress. Please try again.', 429);
            }

            try {
                $this->performSecurityChecks();

                return DB::transaction(function () use ($user) {
                    $activeSubscription = $user->subscriptions()
                        ->where('is_active', true)
                        ->first();

                    if (!$activeSubscription) {
                        throw new Exception('No active subscription found', 404);
                    }

                    $activeSubscription->update([
                        'end_date' => Carbon::now(),
                        'is_active' => false,
                    ]);

                    return ResponseHelper::success([
                        'message' => 'Successfully unsubscribed from package',
                        'subscription' => $activeSubscription->load('package'),
                    ]);
                });
            } finally {
                $lock->release();
            }
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Upgrade user's package
     */
    public function upgrade(string $newPackageType): JsonResponse
    {
        try {
            $user = Auth::user();
            $lock = Cache::lock("upgrade_{$user->id}", 10);

            if (!$lock->get()) {
                throw new Exception('Another upgrade operation is in progress. Please try again.', 429);
            }

            try {
                $account = $this->performSecurityChecks($newPackageType);
                $newPackage = Package::where('type', $newPackageType)->first();

                return DB::transaction(function () use ($user, $account, $newPackage) {
                    $activeSubscription = $user->subscriptions()
                        ->where('is_active', true)
                        ->first();

                    if (!$activeSubscription) {
                        return $this->subscribe($newPackage->type);
                    }

                    $currentPackage = $activeSubscription->package;
                    $currentPrice = (float) str_replace('/month', '', $currentPackage->price);
                    $newPrice = (float) str_replace('/month', '', $newPackage->price);

                    if ($newPrice <= $currentPrice) {
                        throw new Exception('New package must have a higher price for upgrade', 400);
                    }

                    $additionalCost = $newPrice - $currentPrice;
                    if ($additionalCost > $account->balance) {
                        throw new Exception('Insufficient funds for upgrade. Please top up your account.', 400);
                    }

                    $activeSubscription->update([
                        'end_date' => Carbon::now(),
                        'is_active' => false,
                    ]);

                    $newSubscription = Subscription::create([
                        'user_id' => $user->id,
                        'package_id' => $newPackage->id,
                        'start_date' => Carbon::now(),
                        'end_date' => null,
                        'is_active' => true,
                    ]);

                    $transactionData = [
                        'currency' => $account->currency ?? 'NGN',
                        'to_sys_account_id' => null,
                        'to_user_name' => 'WhaleGPT System',
                        'to_user_email' => 'system@whalegpt.com',
                        'to_bank_name' => "WhaleGPT System",
                        'to_bank_code' => "00",
                        'to_account_number' => null,
                        'transaction_reference' => 'UPG_' . $newSubscription->id . '_' . time(),
                        'status' => 'completed',
                        'type' => 'subscription_upgrade',
                        'amount' => $additionalCost,
                        'note' => "Upgrade from {$currentPackage->type} to {$newPackage->type}",
                    ];
                    $this->transactionService->registerTransaction($transactionData, TransferType::WHALE_TO_WHALE);

                    return ResponseHelper::success([
                        'message' => "Successfully upgraded from {$currentPackage->type} to {$newPackage->type}",
                        'subscription' => $newSubscription->load('package'),
                    ]);
                });
            } finally {
                $lock->release();
            }
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Downgrade user's package
     */
    public function downgrade(string $newPackageType): JsonResponse
    {
        try {
            $user = Auth::user();
            $lock = Cache::lock("downgrade_{$user->id}", 10);

            if (!$lock->get()) {
                throw new Exception('Another downgrade operation is in progress. Please try again.', 429);
            }

            try {
                $this->performSecurityChecks();
                $newPackage = Package::where('type', $newPackageType)->first();

                return DB::transaction(function () use ($user, $newPackage) {
                    if (!$newPackage) {
                        throw new Exception('New package not found', 404);
                    }

                    $activeSubscription = $user->subscriptions()
                        ->where('is_active', true)
                        ->first();

                    if (!$activeSubscription) {
                        return $this->subscribe($newPackage->type);
                    }

                    $currentPackage = $activeSubscription->package;
                    $currentPrice = (float) str_replace('/month', '', $currentPackage->price);
                    $newPrice = (float) str_replace('/month', '', $newPackage->price);

                    if ($newPrice >= $currentPrice) {
                        throw new Exception('New package must have a lower price for downgrade', 400);
                    }

                    $activeSubscription->update([
                        'end_date' => Carbon::now(),
                        'is_active' => false,
                    ]);

                    $newSubscription = Subscription::create([
                        'user_id' => $user->id,
                        'package_id' => $newPackage->id,
                        'start_date' => Carbon::now(),
                        'end_date' => null,
                        'is_active' => true,
                    ]);

                    return ResponseHelper::success([
                        'message' => "Successfully downgraded from {$currentPackage->type} to {$newPackage->type}",
                        'subscription' => $newSubscription->load('package'),
                    ]);
                });
            } finally {
                $lock->release();
            }
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}