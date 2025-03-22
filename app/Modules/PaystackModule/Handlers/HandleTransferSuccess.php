<?php

namespace App\Modules\PaystackModule\Services;

use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleTransferSuccess
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::success(); // Implement logic for transfer success
    }
}
