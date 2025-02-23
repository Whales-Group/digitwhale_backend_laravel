<?php

namespace App\Modules\WhaleGPTModule;

use App\Modules\TransferModule\Services\TransactionService;
use App\Modules\TransferModule\Services\TransferResourcesService;
use App\Modules\TransferModule\Services\TransferService;
use App\Modules\WhaleGPTModule\Services\HomeCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class WhaleGPTModuleMain
{
 public TransferService $transferService;
 public TransferResourcesService $transferReourcesService;
 public TransactionService $transactionService;
 public HomeCardService $homeCardService;

 public function __construct(
  TransferService $transferService,
  TransferResourcesService $transferReourcesService,
  TransactionService $transactionService,
  HomeCardService $homeCardService
 ) {
  $this->transferService = $transferService;
  $this->transferReourcesService = $transferReourcesService;
  $this->transactionService = $transactionService;
  $this->homeCardService = $homeCardService;
 }
 public function getTips()
    {
        return $this->homeCardService->getTips();
    }

}
