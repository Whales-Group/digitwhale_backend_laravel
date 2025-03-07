<?php

namespace App\Modules\FlutterWaveModule;

use App\Modules\FlutterWaveModule\Handlers\BaseHandler;
use App\Modules\FlutterWaveModule\Services\FlutterWaveService;
use Illuminate\Http\Request;

class FlutterWaveModule
{
    public BaseHandler $baseHandler;
    public FlutterWaveService $flutterWaveService;

    public function __construct(
        BaseHandler $baseHandler,
    ) {
        $this->baseHandler = $baseHandler;
        $this->flutterWaveService = FlutterWaveService::getInstance();
    }
    public function handleWebhook(Request $request)
    {
        return $this->baseHandler->handle($request);
    }

    /// BILL PAYMENTS
    public function getNetworkBillers()
    {
        $this->flutterWaveService->getNetworkBillers();
    }
    public function getUtilityBillers()
    {
        $this->flutterWaveService->getUtilityBillers();
    }
    public function payNetworkBill()
    {
        $this->flutterWaveService->payNetworkBill();
    }
    public function payUtilityBill()
    {
        $this->flutterWaveService->payUtilityBill();
    }
}
