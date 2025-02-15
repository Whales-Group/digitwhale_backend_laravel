<?php

namespace App\Modules\AccountModule;

use App\Modules\AccountModule\Services\AccountCreationService;
use App\Modules\AccountModule\Services\GetAndUpdateAccountService;
use Illuminate\Http\Client\Request;

class AccountModuleMain
{
    public GetAndUpdateAccountService $getAccountService;

    public AccountCreationService  $accountCreationService;

    public function __construct(
        GetAndUpdateAccountService $getAccountService, 
        AccountCreationService $accountCreationService,
        )
    {
        $this->getAccountService = $getAccountService;
        $this->accountCreationService = $accountCreationService;
    }

    public function createAccount(Request $request){
        return $this->accountCreationService->createAccount($request);
    }
    
    public function getAccounts(){
        return $this->getAccountService->getAccount();
    }

    public function getAccountDetails(Request $request){
        return $this->getAccountService->getAccountDetails($request);
    }

    public function toggleEnabled(Request $request){
        return $this->getAccountService->toggleEnabled($request);
    }

   
}
