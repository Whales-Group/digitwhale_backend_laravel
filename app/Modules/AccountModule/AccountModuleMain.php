<?php

namespace App\Modules\AccountModule;

use App\Common\Helpers\ResponseHelper;
use App\Modules\AccountModule\Services\GetAccountService;

class AccountModuleMain
{
    public GetAccountService $getAccountService;

    public function __construct(GetAccountService $getAccountService)
    {
        $this->getAccountService = $getAccountService;
    }

    public function getAccount(){
        return ResponseHelper::success($this->getAccountService->getAccount()->toArray());
    }
}
