<?php

namespace App\Modules\AccountModule;

use App\Common\Helpers\ResponseHelper;
use App\Modules\AccountModule\Services\AccountCreationService;
use App\Modules\AccountModule\Services\AccountSettingsService;
use App\Modules\AccountModule\Services\GetAccountService;
use Illuminate\Http\Request;

class AccountModuleMain
{
    public GetAccountService $getAccountService;
    public AccountSettingsService $accountSettingsService;

    public function __construct(
        GetAccountService $getAccountService, 
        AccountSettingsService $accountSettingsService
        )
    {
        $this->getAccountService = $getAccountService;
        $this->accountSettingsService = $accountSettingsService;
    }

    public function createAccount(Request $request){
        return $this->getAccountService->createAccount($request);
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
}
