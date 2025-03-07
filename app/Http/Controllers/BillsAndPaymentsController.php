<?php

namespace App\Http\Controllers;

use App\Modules\BillsAndPaymentsModule\BillsAndPaymentsModuleMain;

class BillsAndPaymentsController extends Controller
{
    protected $billsAndPaymentsModule;

    public function __construct(BillsAndPaymentsModuleMain $billsAndPaymentsModule)
    {
        $this->billsAndPaymentsModule = $billsAndPaymentsModule;
    }

    public function getNetworkBillers()
    {
        return $this->billsAndPaymentsModule->getNetworkBillers();
    }
    
    public function getUtilityBillers()
    {
        return $this->billsAndPaymentsModule->getUtilityBillers();
    }

    public function payNetworkBill()
    {
        return $this->billsAndPaymentsModule->payNetworkBill();
    }

    public function payUtilityBill()
    {
        return $this->billsAndPaymentsModule->payUtilityBill();
    }
}