<?php

namespace App\Http\Controllers;

use App\Modules\AccountModule\AccountModuleMain;

class AccountController {

    protected AccountModuleMain $authenticationModuleMain;

    public function __construct(
        AccountModuleMain $authenticationModuleMain
    ) {
        $this->authenticationModuleMain = $authenticationModuleMain;
    }


    
    public function getAccount(){
        return $this->authenticationModuleMain->getAccount();
    }
}