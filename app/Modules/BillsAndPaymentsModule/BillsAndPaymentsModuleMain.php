<?php

namespace App\Modules\BillsAndPaymentsModule;

use App\Modules\BillsAndPaymentsModule\Services\BillService;
use App\Modules\BillsAndPaymentsModule\Services\UtilityService;

class BillsAndPaymentsModuleMain
{
    public BillService $billService;

    /**
     * @param BillService $networkService
     */
    public function __construct(
        BillService $billService,
    )
    {
        $this->billService = $billService;
    }


    public function getBillCategories()
    {
        return $this->billService->getBillCategories();
    }


    public function getBillerByCategory()
    {
        return $this->billService->getBillerByCategory();
    }


    public function getBillerItems()
    {
        return $this->billService->getBillerItems();
    }


    public function validateUserInformation()
    {
        return $this->billService->validateUserInformation();
    }


    public function purchaseBill()
    {
        return $this->billService->purchaseBill();
    }
}
