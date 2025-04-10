<?php

namespace App\Gateways\Paystack\Handlers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleCustomerIdentificationFailed
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::error(); // Implement your logic for handling customer identification failure
    }
}
