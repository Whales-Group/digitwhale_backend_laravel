<?php

namespace App\Gateways\Paystack\Handlers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleTransferReversed
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::success(); // Implement logic for transfer reversal
    }
}
