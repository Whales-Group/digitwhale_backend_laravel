<?php

namespace App\Http\Controllers;

use App\Gateways\FlutterWave\FlutterWaveModule;
use App\Models\AppLog;
use App\Gateways\Fincra\FincraModuleMain;
use App\Gateways\Paystack\PaystackModuleMain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MiscellaneousController extends Controller
{

    public PaystackModuleMain $moduleMain;
    public FincraModuleMain $fincraModule;
    public FlutterWaveModule $flutterWaveModule;

    public function __construct(
        PaystackModuleMain $moduleMain,
        FincraModuleMain $fincraModuleMain,
        FlutterWaveModule $flutterWaveModule,
    ) {
        $this->moduleMain = $moduleMain;
        $this->fincraModule = $fincraModuleMain;
        $this->flutterWaveModule = $flutterWaveModule;

    }

    public function handlePaystackWebhook(Request $request): ?JsonResponse
    {
        return $this->moduleMain->handleWebhook($request);
    }

    public function handleFincraWebhook(Request $request): ?JsonResponse
    {
        AppLog::info("handleFincraWebhook ", ["Request" => $request->all()]);
        return $this->fincraModule->handleWebhook($request);
    }
    public function handleFlutterwaveWebhook(Request $request): ?JsonResponse
    {
        AppLog::info("handleFlutterwaveWebhook ", ["Request" => $request->all()]);
        return $this->flutterWaveModule->handleWebhook($request);
    }
}
