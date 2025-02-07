<?php

namespace App\Modules\AccountModule;

use App\Modules\AccountModule\Services\AccountCreationService;
use App\Modules\AccountModule\Services\AccountSettingsCreationService;
use App\Modules\AccountModule\Services\GetAndUpdateAccountService;
use Illuminate\Http\Request;

class AccountModuleMain
{
    public GetAndUpdateAccountService $getAccountService;

    public AccountCreationService  $accountCreationService;
    public AccountSettingsCreationService $accountSettingsService;

    public function __construct(
        GetAndUpdateAccountService $getAccountService, 
        AccountCreationService $accountCreationService,
        AccountSettingsCreationService $accountSettingsService
        )
    {
        $this->getAccountService = $getAccountService;
        $this->accountCreationService = $accountCreationService;
        $this->accountSettingsService = $accountSettingsService;
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

    public function updateAccount(Request $request){
        return $this->getAccountService->updateAccount($request);
    }

    public function getOrCreateAccountSettings(Request $request){
        return $this->accountSettingsService->getOrCreateAccountSettings($request);
    }

    public function updateIn(Request $request){
        return $this->getAccountService->updateIn($request);
    }
}
