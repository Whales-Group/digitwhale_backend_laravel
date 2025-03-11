<?php

namespace App\Http\Controllers;

use App\Modules\AiModule\AiModuleMain;
use App\Modules\PaystackModule\PaystackModuleMain;
use App\Modules\PaystackModule\Services\PaystackService;
use App\Modules\WhaleGPTModule\WhaleGPTModuleMain;

class WhaleGptController extends Controller
{

 public PaystackModuleMain $paystackModuleMain;
 public WhaleGPTModuleMain $vippsModuleMain;
 public AiModuleMain $aiModuleMain;

 public function __construct(
     PaystackModuleMain $paystackModuleMain,
     WhaleGPTModuleMain $vippsModuleMain,
     AiModuleMain $aiModuleMain

 ) {
     $this->paystackModuleMain = $paystackModuleMain;
     $this->vippsModuleMain = $vippsModuleMain;
     $this->aiModuleMain = $aiModuleMain;

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
        return $this->vippsModuleMain->getTips();
    }

    public function conversation()
    {
        return $this->aiModuleMain->processQuery();
    }
}