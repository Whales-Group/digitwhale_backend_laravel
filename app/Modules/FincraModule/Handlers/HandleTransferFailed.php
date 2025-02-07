<?php

namespace App\Modules\FincraModule\Handlers;

use App\Common\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleTransferFailed
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::error(); // Implement logic for transfer failure
    }
}
