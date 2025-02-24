<?php

namespace App\Modules\BillsAndPaymentsModule;

use App\Modules\FlutterWaveModule\Services\FlutterWaveService;

class BillsAndPaymentsModuleMain
{
 public FlutterWaveService $flutterWaveService;
 public function __construct(
  FlutterWaveService $flutterWaveService
 ) {
  $this->flutterWaveService = $flutterWaveService;
 }

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
