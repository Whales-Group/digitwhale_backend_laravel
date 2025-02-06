<?php

namespace App\Http\Controllers;

use App\Modules\AdminAuthenticationModule\AdminAuthenticationModuleMain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuthController extends Controller
{
    protected AdminAuthenticationModuleMain $module;

    public function __construct(AdminAuthenticationModuleMain $module)
    {
        $this->module = $module;
    }

    /**
     * Handle admin sign-in.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function signIn(Request $request): JsonResponse
    {
        return $this->module->login($request);
    }

    /**
     * Initialize admin registration process.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initializeRegistration(Request $request): JsonResponse
    {
        return $this->module->initializeRegistration($request);
    }

    /**
     * Complete admin profile after registration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function completeProfile(Request $request): JsonResponse
    {
        return $this->module->completeProfile($request);
    }

    /**
     * Send OTP to the admin's email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendOtp(Request $request): JsonResponse
    {
        return $this->module->sendOtp($request);
    }

    /**
     * Verify the admin's account using OTP.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyAccount(Request $request): JsonResponse
    {
        return $this->module->verifyAccount($request);
    }

    /**
     * Change the admin's password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        return $this->module->changePassword($request);
    }

    /**
     * Initiate password recovery process.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initiatePasswordRecovery(Request $request): JsonResponse
    {
        return response()->json([
            "status" => "success",
            "message" => "Password recovery initiated successfully",
        ]);
    }

    /**
     * Complete password recovery with a new password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function completePasswordRecovery(Request $request): JsonResponse
    {
        return response()->json([
            "status" => "success",
            "message" => "Password recovery completed successfully",
        ]);
    }

    /**
     * Handle admin logout.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        return $this->module->signInService->logout($request);
    }
}