<?php

namespace App\Http\Controllers;

use App\Modules\PaystackModule\PaystackModuleMain;
use App\Modules\PaystackModule\Services\PaystackService;

class WhaleGptController extends Controller
{

 public PaystackModuleMain $paystackModuleMain;

 public function __construct(
     PaystackModuleMain $paystackModuleMain
 ) {
     $this->paystackModuleMain = $paystackModuleMain;
 }
 public function generatePaymentLink()
 {   
     return $this->paystackModuleMain->generatePaymentLink();
 }

    public function verifyPayment()
    {
        return $this->paystackModuleMain->verifyPayment();
    }
}