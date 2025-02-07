<?php

namespace App\Http\Controllers;

use App\Modules\AccountModule\AccountModuleMain;
use App\Modules\AccountSettingModule\AccountSettingModuleMain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountSettingController extends Controller
{
    protected AccountSettingModuleMain $accountModuleMain;


    public function __construct(AccountSettingModuleMain $accountModuleMain)
    {
        $this->accountModuleMain = $accountModuleMain;
    }


    public function getOrCreateAccountSettings(Request $request)
    {
        return $this->accountModuleMain->getOrCreateAccountSettings($request);
    }

    public function toggleEnabled(Request $request)
    {
        return $this->accountModuleMain->toggleEnabled($request);

    }

    public function updateAccountSettings(Request $request)
    {
        return $this->accountModuleMain->updateAccountSettings($request);

    }


    public function addSecurityQuestion(Request $request)
    {
        return $this->accountModuleMain->addSecurityQuestion($request);

    }


    public function addOrUpdateNextofKin(Request $request)
    {
        return $this->accountModuleMain->addOrUpdateNextofKin($request);

    }



    public function updateTag(Request $request)
    {
        return $this->accountModuleMain->updateTag($request);

    }


    public function changePassword(Request $request)
    {
        return $this->accountModuleMain->changePassword($request);

    }


    public function createOrUpdateTransactionPin(Request $request)
    {
        return $this->accountModuleMain->createOrUpdateTransactionPin($request);

    }


    public function toggleBalanceVisibility(Request $request)
    {
        return $this->accountModuleMain->toggleBalanceVisibility($request);

    }


    public function toggleBiometrics(Request $request)
    {
        return $this->accountModuleMain->toggleBiometrics($request);

    }


    public function toggleAirTransfer(Request $request)
    {
        return $this->accountModuleMain->toggleAirTransfer($request);

    }


}