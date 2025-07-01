<?php

namespace App\Http\Controllers;

use App\Modules\BillsAndPaymentsModule\BillsAndPaymentsModuleMain;

class BillAndUtilsController extends Controller
{
    public BillsAndPaymentsModuleMain $billAndPaymentsModuleMain;

    public function __construct(
        BillsAndPaymentsModuleMain $billAndPaymentsModuleMain,

    )
    {
        $this->billAndPaymentsModuleMain = $billAndPaymentsModuleMain;
    }


    public function getBillCategories()
    {
        return $this->billAndPaymentsModuleMain->getBillCategories();
    }


    public function getBillerByCategory()
    {
        return $this->billAndPaymentsModuleMain->getBillerByCategory();
    }

    public function getBillerItems()
    {
        return $this->billAndPaymentsModuleMain->getBillerItems();
    }


    public function validateUserInformation()
    {
        return $this->billAndPaymentsModuleMain->validateUserInformation();
    }


    public function purchaseBill()
    {
        return $this->billAndPaymentsModuleMain->purchaseBill();
    }
}
