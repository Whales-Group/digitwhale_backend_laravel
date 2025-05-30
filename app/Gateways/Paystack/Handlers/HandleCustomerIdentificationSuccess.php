<?php

namespace App\Gateways\Paystack\Handlers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleCustomerIdentificationSuccess
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::success(); // Implement your logic for handling customer identification success
    }
}
