<?php

namespace App\Http\Controllers;

use App\Modules\AiModule\AiModuleMain;
use App\Modules\PaystackModule\PaystackModuleMain;
use App\Modules\PaystackModule\Services\PaystackService;
use App\Modules\UtilsModule\UtilsModuleMain;

class UtilsController extends Controller
{

    public PaystackModuleMain $paystackModuleMain;
    public UtilsModuleMain $utilsModuleMain;

    public function __construct(
        PaystackModuleMain $paystackModuleMain,
        UtilsModuleMain $utilsModuleMain,

    ) {
        $this->paystackModuleMain = $paystackModuleMain;
        $this->utilsModuleMain = $utilsModuleMain;

    }
    public function generatePaymentLink()
    {
        return $this->paystackModuleMain->generatePaymentLink();
    }

    public function verifyPayment()
    {
        return $this->paystackModuleMain->verifyPayment();
    }

    public function getTips()
    {
        return $this->utilsModuleMain->getTips();
    }


    public function getPackages()
    {
        return $this->utilsModuleMain->getPackages();
    }

    public function subscribe($packageType)
    {
        return $this->utilsModuleMain->subscribe($packageType);
    }

    public function unsubscribe()
    {
        return $this->utilsModuleMain->unsubscribe();
    }

    public function upgrade($newPackageType)
    {
        return $this->utilsModuleMain->upgrade($newPackageType);
    }

    public function downgrade($newPackageType)
    {
        return $this->utilsModuleMain->downgrade($newPackageType);
    }
}