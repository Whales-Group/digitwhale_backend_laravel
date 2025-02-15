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

    public function createAccount(){
        return $this->accountCreationService->createAccount();
    }
    
    public function getAccounts(){
        return $this->getAccountService->getAccount();
    }

    public function getAccountDetails(){
        return $this->getAccountService->getAccountDetails();
    }

    public function toggleEnabled(){
        return $this->getAccountService->toggleEnabled();
    }

   
}
