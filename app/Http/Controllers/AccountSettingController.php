<?php

namespace App\Http\Controllers;

use App\Modules\AccountSettingModule\AccountSettingModuleMain;
use Illuminate\Http\Client\Request;

class AccountSettingController extends Controller
{
    protected AccountSettingModuleMain $accountModuleMain;


    public function __construct(AccountSettingModuleMain $accountModuleMain)
    {
        $this->accountModuleMain = $accountModuleMain;
    }


    public function getOrCreateAccountSettings()
    {
        return $this->accountModuleMain->getOrCreateAccountSettings();
    }

    public function toggleEnabled(Request $request)
    {
        return $this->accountModuleMain->toggleEnabled($request);

    }

    public function updateAccountSettings()
    {
        return $this->accountModuleMain->updateAccountSettings();

    }
}