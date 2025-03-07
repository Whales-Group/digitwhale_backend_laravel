<?php

namespace App\Modules\BillsAndPaymentsModule;

use App\Modules\BillsAndPaymentsModule\Services\NetworkBillsService;
use App\Modules\BillsAndPaymentsModule\Services\UtilityBillsService;
use App\Modules\FlutterWaveModule\FlutterWaveModule;
use App\Modules\FlutterWaveModule\Services\FlutterWaveService;

class BillsAndPaymentsModuleMain
{
    public NetworkBillsService $networkBillsService;
    public UtilityBillsService $utilityBillsService;


    public function __construct(
        NetworkBillsService $networkBillsService,
        UtilityBillsService $utilityBillsService
    ) {
       
        $this->networkBillsService = $networkBillsService;
        $this->utilityBillsService = $utilityBillsService;
    }

    public function getNetworkBillers()
    {
        $this->networkBillsService->getNetworkBillers();
    }
    public function getUtilityBillers()
    {
        $this->utilityBillsService->getUtilityBillers();
    }
    public function payNetworkBill()
    {
        $this->networkBillsService->payNetworkBill();
    }
    public function payUtilityBill()
    {
        $this->utilityBillsService->payUtilityBill();
    }
}
