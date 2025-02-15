<?php

namespace App\Http\Controllers;

use App\Modules\AuthenticationModule\AuthenticationModuleMain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthenticationModuleMain $authenticationModuleMain;

    public function __construct(
        AuthenticationModuleMain $authenticationModuleMain
    ) {
        $this->authenticationModuleMain = $authenticationModuleMain;
    }

    /**
     * Handle user sign-in.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function signIn(Request $request): JsonResponse
    {
        return $this->authenticationModuleMain->signInService->login($request);
    }

    /**
     * Handle initialize registration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initializeRegistration(Request $request): JsonResponse
    {
        return $this->authenticationModuleMain->accountCreationService->initializeRegistration(
            $request
        );
    }


    /**
     * Handle user registration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function completeProfile(Request $request): JsonResponse
    {
        return $this->authenticationModuleMain->accountCreationService->updateProfile(
            $request
        );
    }

    /**
     * Send OTP for account creation or recovery.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendOtp(Request $request): mixed
    {
        return $this->authenticationModuleMain->accountCreationService->sendOtp(
            $request
        );
    }

    /**
     * Verify account using OTP.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyAccount(Request $request): mixed
    {
        return $this->authenticationModuleMain->accountCreationService->verifyAccount(
            $request
        );
    }

    /**
     * Change user password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        return $this->authenticationModuleMain->changePasswordService->changePassword(
            $request
        );
    }

    /**
     * Initiate password recovery process.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initiatePasswordRecovery(Request $request): void
    {
        // return $this->authenticationModuleMain->initiatePasswordRecovery(
        //     $request
        // );
    }

    /**
     * Complete password recovery with new password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function completePasswordRecovery(Request $request): void
    {
        // return $this->authenticationModuleMain->completePasswordRecovery(
        //     $request
        // );
    }

    /**
     * Handle user logout.
     *
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        return $this->authenticationModuleMain->signInService->logout($request);
    }

    /**
     * Handle user logout.
     *
     * @return JsonResponse
     */
    public function getAuthenticatedUser(): JsonResponse
    {
        return $this->authenticationModuleMain->getAuthenticatedUser();
    }


}

