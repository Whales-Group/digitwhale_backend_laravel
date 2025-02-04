<?php

namespace App\Modules\AuthenticationModule;

use App\Modules\AuthenticationModule\Services\SignInService;
use App\Modules\AuthenticationModule\Services\AccountCreationService;
use App\Modules\AuthenticationModule\Services\ChangePasswordService;
// use App\Modules\AuthenticationModule\Services\AccountRecoveryService;
use Illuminate\Http\Request;

class AuthenticationModuleMain
{
    public $signInService;
    public $accountCreationService;
    public $changePasswordService;
    // protected $accountRecoveryService;

    public function __construct(
        SignInService $signInService,
        AccountCreationService $accountCreationService,
        ChangePasswordService $changePasswordService,
        // AccountRecoveryService $accountRecoveryService
    ) {
        $this->signInService = $signInService;
        $this->accountCreationService = $accountCreationService;
        $this->changePasswordService = $changePasswordService;
        // $this->accountRecoveryService = $accountRecoveryService;
    }

    public function login(Request $request)
    {
        return $this->signInService->login($request);
    }

    public function register(Request $request)
    {
        return $this->accountCreationService->register($request);
    }

    public function sendOtp(Request $request)
    {
        return $this->accountCreationService->sendOtp($request);
    }

    public function verifyAccount(Request $request)
    {
        return $this->accountCreationService->verifyAccount($request);
    }

    public function changePassword(Request $request)
    {
        return $this->changePasswordService->changePassword($request);
    }

    public function initiatePasswordRecovery(Request $request)
    {
        // return $this->accountRecoveryService->initiatePasswordRecovery(
        //     $request
        // );
    }

    public function completePasswordRecovery(Request $request)
    {
        // return $this->accountRecoveryService->completePasswordRecovery(
        //     $request
        // );
    }
}
