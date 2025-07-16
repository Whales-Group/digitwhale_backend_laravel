<?php

namespace App\Modules\TransferModule\Services;

use App\Exceptions\AppException;
use App\Helpers\ResponseHelper;
use App\Models\BlockedLiveLocationUsers;
use App\Models\LiveLocation;
use App\Models\LiveLocationPreference;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;


class AirtransferService
{
    /**
     * Get nearby users within a geographic range (e.g., 1km) based on live location.
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $limit
     * @return array
     */
    public function getNearestUsers(float $latitude, float $longitude, int $limit = 5): JsonResponse
    {
        $user = Auth::user();

        // Blocked users cache
        $excludedUserIds = Cache::remember("blocked_users_{$user->id}", 60, function () use ($user) {
            $blocked = $user->blockedLiveLocationUsers()
                ->where('status', 'active')
                ->pluck('blocked_user_id')
                ->toArray();

            $blockedBy = BlockedLiveLocationUsers::where('blocked_user_id', $user->id)
                ->where('status', 'active')
                ->pluck('user_id')
                ->toArray();

            return array_merge($blocked, $blockedBy);
        });

        // // ~5 meters ≈ 0.000045 degrees lat/lng range
        // $range = 0.000045;

        // ~100 centimeters ≈ 0.000009 degrees lat/lng range
        $range = 0.000009;

        // ~100 centimeters ≈ 2.0 degrees lat/lng range
        $range = 10.0;

        $nearbyUsers = LiveLocation::with(['user.accounts']) // eager-load all accounts
            ->whereNotIn('user_id', $excludedUserIds)
            ->where('user_id', '!=', $user->id)
            ->whereBetween('latitude', [$latitude - $range, $latitude + $range])
            ->whereBetween('longitude', [$longitude - $range, $longitude + $range])
            ->limit($limit)
            ->get()
            ->map(function ($loc) {
                $account = $loc->user->accounts->first(); // get the first linked account
    
                return [
                    'latitude' => (float) $loc->latitude,
                    'longitude' => (float) $loc->longitude,
                    'account_name' => $loc->profile_type == "corporate" ? $loc->business_name : $loc->user->first_name . ' ' . $loc->user->last_name,
                    'profile_url' => $loc->user->profile_url,
                    'reference_id' => $account?->account_id ?? '',
                    'reference_number' => $account?->account_number,
                    'enabled' => $account?->enabled,
                    'blacklisted' => $account?->blacklisted,
                    'currency' => $account?->currency,
                    'reference_type' => $account?->account_type,
                    'email' => $account?->email,
                ];
            });

        return ResponseHelper::success(data: $nearbyUsers);
    }


    /**
     * Update current user's live location for AirTransfer visibility.
     *
     * @param float $latitude
     * @param float $longitude
     * @return bool
     */
    public function updateLiveLocation(float $latitude, float $longitude): JsonResponse
    {
        $user = Auth::user();

        $liveLocation = LiveLocation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]
        );

        if (!$liveLocation) {
            throw new AppException('Failed to update live location. Please try again.');
        }

        return ResponseHelper::success(data: $liveLocation);
    }


    /**
     * Get the current AirTransfer preferences for the authenticated user.
     *
     * @return array
     */
    public function getAirTransferPreference(): JsonResponse
    {
        $userId = Auth::id();

        $preference = LiveLocationPreference::firstOrCreate(
            ['user_id' => $userId],
            [
                'visibility' => false,
                'visibility_timer' => 0,
                'profile_picture_visibility' => true,
                'notify_on_visible' => false
            ]
        );

        return ResponseHelper::success(data: $preference);
    }

    /**
     * Set user preferences for AirTransfer visibility and discoverability.
     *
     * @param bool $isBlocked
     * @param int $visibilityTimeInMin
     * @return bool
     */
    public function setAirTransferPreference(array $data): JsonResponse
    {
        $userId = Auth::id();

        return ResponseHelper::success(data: LiveLocationPreference::updateOrCreate(
            ['user_id' => $userId],
            $data
        ));
    }

}
