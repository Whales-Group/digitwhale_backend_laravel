<?php

namespace App\Modules\PaystackWebhookModule\Services;

use App\Common\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleTransferSuccess
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::success(); // Implement logic for transfer success
    }
}
