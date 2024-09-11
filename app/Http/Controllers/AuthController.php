<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AuthenticationModule\Services\SignInService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Common\Helpers\ResponseHelpers;

class AuthController extends Controller
{
    protected SignInService $signInService;

    /**
     * Handle user sign-in.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function signIn(Request $request): JsonResponse
    {
    }

    /**
     * Handle user logout.
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        $this->signInService->logout();

        return ResponseHelpers::success("Successfully logged out");
    }
}
