<?php

namespace App\Modules\AccountSettingModule;

use App\Modules\AccountModule\Services\GetAndUpdateAccountService;
use App\Modules\AccountSettingModule\Services\AccountSettingsCreationService;
use App\Modules\AccountSettingModule\Services\AccountSettingsUpdateService;
use Illuminate\Http\Client\Request;

class AccountSettingModuleMain
{

    public $accountCreationService;
    public $getAndUpdateAccountService;

    public $accountSettingsUpdateService;


    public function __construct(
        AccountSettingsCreationService $accountCreationService,
        GetAndUpdateAccountService $getAndUpdateAccountService,
        AccountSettingsUpdateService $accountSettingsUpdateService,
    ) {
        $this->accountCreationService = $accountCreationService;
        $this->getAndUpdateAccountService = $getAndUpdateAccountService;
        $this->accountSettingsUpdateService = $accountSettingsUpdateService;
    }


    public function getOrCreateAccountSettings()
    {
        return $this->accountCreationService->getOrCreateAccountSettings();
    }

    public function toggleEnabled(Request $request)
    {
        return $this->getAndUpdateAccountService->toggleEnabled($request);
    }

    public function updateAccountSettings()
    {
        return $this->accountSettingsUpdateService->updateAccountSettings();
    }

}