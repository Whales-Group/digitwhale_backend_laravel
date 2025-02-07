<?php

namespace App\Modules\AccountSettingModule;

use App\Common\Helpers\DateHelper;
use App\Common\Helpers\ResponseHelper;
use App\Modules\AccountModule\Services\GetAndUpdateAccountService;
use App\Modules\AccountSettingModule\Services\AccountSettingsCreationService;
use App\Modules\AccountSettingModule\Services\AccountSettingsUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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


    public function getOrCreateAccountSettings(Request $request)
    {
        return $this->accountCreationService->getOrCreateAccountSettings($request);
    }

    public function toggleEnabled(Request $request)
    {
        return $this->getAndUpdateAccountService->toggleEnabled($request);
    }

    public function updateAccountSettings(Request $request)
    {
        return $this->accountSettingsUpdateService->updateAccountSettings($request);
    }

    public function addSecurityQuestion(Request $request)
    {
        return $this->accountSettingsUpdateService->addSecurityQuestion($request);
    }

    public function addOrUpdateNextofKin(Request $request)
    {
        return $this->accountSettingsUpdateService->addOrUpdateNextofKin($request);
    }


    public function updateTag(Request $request)
    {
        return $this->accountSettingsUpdateService->updateTag($request);
    }

    public function changePassword(Request $request)
    {
        return $this->accountSettingsUpdateService->changePassword($request);
    }


    public function createOrUpdateTransactionPin(Request $request)
    {
        return $this->accountSettingsUpdateService->createOrUpdateTransactionPin($request);
    }

    public function toggleBalanceVisibility(Request $request)
    {
        return $this->accountSettingsUpdateService->toggleBalanceVisibility($request);
    }

    public function toggleBiometrics(Request $request)
    {
        return $this->accountSettingsUpdateService->toggleBiometrics($request);
    }

    public function toggleAirTransfer(Request $request)
    {
        return $this->accountSettingsUpdateService->toggleAirTransfer($request);
    }


}