<?php

namespace App\Modules\BillsAndPaymentsModule\Services;

use App\Modules\FlutterWaveModule\FlutterWaveModule;

class UtilityBillsService
{
    public FlutterWaveModule $flutterWaveModule;
    public function __construct(
        FlutterWaveModule $flutterWaveModule
    ) {
        $this->flutterWaveModule = $flutterWaveModule;
    }


    public function getUtilityBillers()
    {
        $this->flutterWaveModule->getUtilityBillers();
    }
  
    public function payUtilityBill()
    {
        $this->flutterWaveModule->payUtilityBill();
    }
}
