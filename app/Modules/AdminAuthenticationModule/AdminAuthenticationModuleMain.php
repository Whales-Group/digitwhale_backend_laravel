<?php

namespace App\Modules\AdminAuthenticationModule;

use App\Modules\AdminAuthenticationModule\Services\AdminRegistrationService;
use App\Modules\AdminAuthenticationModule\Services\AdminRolePermissionService;
use App\Modules\AdminAuthenticationModule\Services\ChangePasswordService;
use App\Modules\AdminAuthenticationModule\Services\SignInService;
use Illuminate\Http\Request;

class AdminAuthenticationModuleMain
{
    public $signInService;
    public $accountCreationService;
    public $changePasswordService;

    public function __construct(
        SignInService $signInService,
        AdminRegistrationService $accountCreationService,
        ChangePasswordService $changePasswordService,
    ) {
        $this->signInService = $signInService;
        $this->accountCreationService = $accountCreationService;
        $this->changePasswordService = $changePasswordService;
    }

    /**
     * Handle admin login.
     *
     * @param Request $request
     * @return mixed
     */
    public function login(Request $request)
    {
        return $this->signInService->login($request);
    }

    /**
     * Initialize admin registration process.
     *
     * @param Request $request
     * @return mixed
     */
    public function initializeRegistration(Request $request)
    {
        return $this->accountCreationService->initializeAdminRegistration($request);
    }

    /**
     * Complete admin profile after registration.
     *
     * @param Request $request
     * @return mixed
     */
    public function completeProfile(Request $request)
    {
        return $this->accountCreationService->updateAdminProfile($request);
    }

    /**
     * Send OTP to the admin's email.
     *
     * @param Request $request
     * @return mixed
     */
    public function sendOtp(Request $request)
    {
        return $this->accountCreationService->sendAdminOtp($request);
    }

    /**
     * Verify the admin's account using OTP.
     *
     * @param Request $request
     * @return mixed
     */
    public function verifyAccount(Request $request)
    {
        return $this->accountCreationService->verifyAdminAccount($request);
    }

    /**
     * Change the admin's password.
     *
     * @param Request $request
     * @return mixed
     */
    public function changePassword(Request $request)
    {
        return $this->changePasswordService->changePassword($request);
    }

    
}