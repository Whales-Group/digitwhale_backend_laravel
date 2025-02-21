<?php

namespace App\Modules\WhaleGPTModule\Services;

use App\Common\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\Package;
use App\Models\Subscription;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PackageService
{
    public function __construct()
    {
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

        // Balance check only if packageType is provided (for subscribe/upgrade)
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
            // Perform security checks with package type for balance verification
            $account = $this->performSecurityChecks($packageType);

            $user = Auth::user();
            $package = Package::where('type', $packageType)->first();

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

            // Deduct balance if applicable
            if ($package->price !== '0/month') {
                $packagePrice = (float) str_replace('/month', '', $package->price);
                $account->update(['balance' => $account->balance - $packagePrice]);
            }

            return ResponseHelper::success([
                'message' => "Successfully subscribed to {$package->type} package",
                'subscription' => $subscription->load('package'),
            ]);
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
            // Perform security checks without balance check
            $this->performSecurityChecks();

            $user = Auth::user();
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
            // Perform security checks with new package type for balance verification
            $account = $this->performSecurityChecks($newPackageType);

            $user = Auth::user();
            $newPackage = Package::where('type', $newPackageType)->first();

            $activeSubscription = $user->subscriptions()
                ->where('is_active', true)
                ->first();

            if (!$activeSubscription) {
                return $this->subscribe($newPackageType);
            }

            $currentPackage = $activeSubscription->package;
            $currentPrice = (float) str_replace('/month', '', $currentPackage->price);
            $newPrice = (float) str_replace('/month', '', $newPackage->price);

            if ($newPrice <= $currentPrice) {
                throw new Exception('New package must have a higher price for upgrade', 400);
            }

            // Calculate additional cost
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

            // Deduct additional cost
            $account->update(['balance' => $account->balance - $additionalCost]);

            return ResponseHelper::success([
                'message' => "Successfully upgraded from {$currentPackage->type} to {$newPackage->type}",
                'subscription' => $newSubscription->load('package'),
            ]);
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
            // Perform security checks without balance check
            $this->performSecurityChecks();

            $user = Auth::user();
            $newPackage = Package::where('type', $newPackageType)->first();
            
            if (!$newPackage) {
                throw new Exception('New package not found', 404);
            }

            $activeSubscription = $user->subscriptions()
                ->where('is_active', true)
                ->first();

            if (!$activeSubscription) {
                return $this->subscribe($newPackageType);
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
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}