<?php

namespace App\Modules\PaystackModule\Services;

use App\Common\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleDedicatedAccountAssignFailed
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::error(); // Implement logic for dedicated account assignment failure
    }
}
