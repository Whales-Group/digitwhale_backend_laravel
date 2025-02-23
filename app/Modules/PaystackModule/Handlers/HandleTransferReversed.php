<?php

namespace App\Modules\PaystackWebhookModule\Services;

use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleTransferReversed
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::success(); // Implement logic for transfer reversal
    }
}
