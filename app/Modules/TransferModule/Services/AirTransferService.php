<?php

namespace App\Modules\TransferModule\Services;

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
    public function getNearestUsers(float $latitude, float $longitude, int $limit = 5): array
    {
        // Imagine we use Redis Geo or Haversine SQL here
        return [
            // Reference Id is Account Generated ID ()
            ['latitude' => 130423.32, 'longitude' => 130423.32, 'name' => 'Jesse Dan', 'reference_id'=> ''],
            ['longitude' => 130423.32, 'latitude' => 130423.32, 'name' => 'Vivian Dagbue',  'reference_id'=> ''],
        ];
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
        $userId = Auth::id();

        Cache::put("airtransfer:location:$userId", [
            'lat' => $latitude,
            'lng' => $longitude,
            'timestamp' => now(),
        ], now()->addMinutes(10));

        return true;
    }

    /**
     * Toggle whether the current user can be seen by others nearby.
     *
     * @param bool $block
     * @return bool
     */
    public function toggleBlockSelfFromUserLive(bool $block): bool
    {
        $userId = Auth::id();

        Cache::put("airtransfer:block:$userId", $block, now()->addDays(1));

        return $block;
    }

    /**
     * Set how long (in minutes) the user's visibility should last after going online.
     *
     * @param int $durationInMinutes
     * @return bool
     */
    public function setVisibilityTimeframeInMin(int $durationInMinutes): bool
    {
        $userId = Auth::id();

        Cache::put("airtransfer:visibility_time:$userId", $durationInMinutes, now()->addDays(1));

        return true;
    }

    /**
     * Get the current AirTransfer preferences for the authenticated user.
     *
     * @return array
     */
    public function getAirTransferPreference(): array
    {
        $userId = Auth::id();

        return [
            'blocked' => Cache::get("airtransfer:block:$userId", false),
            'visibility_time' => Cache::get("airtransfer:visibility_time:$userId", 10),
        ];
    }

    /**
     * Set user preferences for AirTransfer visibility and discoverability.
     *
     * @param bool $isBlocked
     * @param int $visibilityTimeInMin
     * @return bool
     */
    public function setAirTransferPreference(bool $isBlocked, int $visibilityTimeInMin): bool
    {
        $this->toggleBlockSelfFromUserLive($isBlocked);
        $this->setVisibilityTimeframeInMin($visibilityTimeInMin);

        return true;
    }
}
