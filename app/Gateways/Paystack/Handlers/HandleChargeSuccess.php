<?php

namespace App\Gateways\Paystack\Handlers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleChargeSuccess
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::success();
    }
}
