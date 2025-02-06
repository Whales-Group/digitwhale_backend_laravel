<?php

namespace App\Modules\FincraModule\Handlers;

use App\Common\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;

class HandleChargeSuccess
{
    public static function handle(): ?JsonResponse
    {
        return ResponseHelper::success();
    }
}
