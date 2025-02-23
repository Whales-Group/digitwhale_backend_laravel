<?php

namespace App\Modules\PaystackWebhookModule\Services;

use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleDedicatedAccountAssignSuccess
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::success(); // Implement logic for dedicated account assignment success
    }
}
