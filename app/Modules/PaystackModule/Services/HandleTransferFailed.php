<?php

namespace App\Modules\PaystackModule\Services;

use App\Common\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleTransferFailed
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::error(); // Implement logic for transfer failure
    }
}
