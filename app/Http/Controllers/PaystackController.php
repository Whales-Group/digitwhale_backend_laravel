<?php

namespace App\Http\Controllers;

use App\Modules\PaystackModule\PaystackModuleMain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaystackController extends Controller
{
    public function handleCallbacks(Request $request): ?JsonResponse
    {
        return PaystackModuleMain::handle($request);
    }
}
