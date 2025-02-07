<?php

namespace App\Modules\TransferModule\Services;

use App\Common\Helpers\ResponseHelper;
use GuzzleHttp\Psr7\Request;

class TransferService
{

    public function transfer(Request $request, string $account_id)
    {
        return ResponseHelper::success('transfer hit');
    }
}
