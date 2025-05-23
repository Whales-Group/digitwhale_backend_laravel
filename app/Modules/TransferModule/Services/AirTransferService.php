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

        // Cache the blocked user IDs for this user to avoid querying repeatedly
        $excludedUserIds = Cache::remember("blocked_users_{$user->id}", 60, function () use ($user) {

            // Get user IDs the current user has blocked
            $blockedUserIds = $user->blockedLiveLocationUsers()->where('status', 'active')->pluck('blocked_user_id')->toArray();

            // Get user IDs that have blocked the current user
            $blockedByUserIds = BlockedLiveLocationUsers::where('blocked_user_id', $user->id)
                ->where('status', 'active')
                ->pluck('user_id')
                ->toArray();

            return array_merge($blockedUserIds, $blockedByUserIds);
        });

        // TODO: ADD CHECK FOR EACH OF THE USER PREFERERENFECS LIKE VISIBILITY ETC

        // Use the Haversine formula to find users close to the given location
        $nearbyUsers = LiveLocation::join('users', 'live_locations.user_id', '=', 'users.id')
            ->join('accounts', 'users.account_id', '=', 'accounts.id')
            ->whereNotIn('users.id', $excludedUserIds)
            ->where('users.id', '!=', $user->id)
            ->select(
                'users.first_name',
                'users.last_name',
                'users.profile_url',
                'live_locations.latitude',
                'live_locations.longitude',
                'accounts.account_id as reference_id',
                DB::raw("(
             6371 * acos(
                 cos(radians(?)) *
                 cos(radians(live_locations.latitude)) *
                 cos(radians(live_locations.longitude) - radians(?)) +
                 sin(radians(?)) *
                 sin(radians(live_locations.latitude))
             )
         ) AS distance", [$latitude, $longitude, $latitude])
            )
            ->orderBy('distance')
            ->limit($limit)
            ->get()
            ->map(function ($liveLocation) {
                return [
                    'latitude' => (float) $liveLocation->latitude,
                    'longitude' => (float) $liveLocation->longitude,
                    'name' => $liveLocation->user->first_name . " " . $liveLocation->user->last_name,
                    'profile_url' => $liveLocation->user->profile_url,
                    'reference_id' => $liveLocation->reference_id ?? '',
                ];
            })
            ->toArray();

        return ResponseHelper::success(data: $nearbyUsers);
    }

    /**
     * Update current user's live location for AirTransfer visibility.
     *
     * @param float $latitude
     * @param float $longitude
     * @return bool
     */
    public function updateLiveLocation(float $latitude, float $longitude): bool
    {
        $user = Auth::user();

        $liveLocation = $user->liveLocation()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'latitude' => $latitude,
                'longitude' => $longitude
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
    public function setAirTransferPreference(array $data):JsonResponse
    {
        $userId = Auth::id();
    
        return ResponseHelper::success(data: LiveLocationPreference::updateOrCreate(
            ['user_id' => $userId],
            $data
        ));
    }

}
