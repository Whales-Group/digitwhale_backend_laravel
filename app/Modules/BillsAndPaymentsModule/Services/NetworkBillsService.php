<?php

namespace App\Modules\BillsAndPaymentsModule\Services;

use App\Modules\FlutterWaveModule\FlutterWaveModule;

class NetworkBillsService
{
    public FlutterWaveModule $flutterWaveModule;
    public function __construct(
        FlutterWaveModule $flutterWaveModule
    ) {
        $this->flutterWaveModule = $flutterWaveModule;
    }


    public function getNetworkBillers()
    {
        $this->flutterWaveModule->getNetworkBillers();
    }

    public function payNetworkBill()
    {
        $this->flutterWaveModule->payNetworkBill();
    }
}
