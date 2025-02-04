<?php

namespace App\Http\Controllers;

use App\Modules\PaystackModule\PaystackModuleMain;
use App\Modules\PaystackWebhookModule\PaystackWebhookModuleMain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MiscellaneousController extends Controller
{

    public PaystackModuleMain $moduleMain;

    public function __construct(
        PaystackModuleMain $moduleMain,
    ) {
        $this->moduleMain = $moduleMain;
    }
    
    public function handleCallbacks(Request $request): ?JsonResponse
    {
        return $this->moduleMain->handleWebhook($request);
    }
}
