<?php

namespace App\Http\Controllers;

use App\Modules\FincraModule\FincraModuleMain;
use App\Modules\PaystackModule\PaystackModuleMain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MiscellaneousController extends Controller
{

    public PaystackModuleMain $moduleMain;
    public FincraModuleMain $fincraModule;

    public function __construct(
        PaystackModuleMain $moduleMain,
        FincraModuleMain $fincraModuleMain
    ) {
        $this->moduleMain = $moduleMain;
        $this->fincraModule = $fincraModuleMain;
    }

    public function handlePaystackWebhook(Request $request): ?JsonResponse
    {
        return $this->moduleMain->handleWebhook($request);
    }

    public function handleFincraWebhook(Request $request): ?JsonResponse
    {
        Log::info("handleFincraWebhook ", ["Request" => $request->all()]);
        return $this->fincraModule->handleWebhook($request);
    }
}
