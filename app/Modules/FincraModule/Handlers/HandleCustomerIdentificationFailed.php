<?php

namespace App\Modules\FincraModule\Handlers;

use App\Common\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleCustomerIdentificationFailed
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::error(); // Implement your logic for handling customer identification failure
    }
}
