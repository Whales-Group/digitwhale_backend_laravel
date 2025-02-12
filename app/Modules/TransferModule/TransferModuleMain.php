<?php

namespace App\Modules\TransferModule;

use App\Common\Enums\TransactionStatus;
use App\Modules\TransferModule\Services\TransactionService;
use App\Modules\TransferModule\Services\TransferResourcesService;
use App\Modules\TransferModule\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class TransferModuleMain
{
    public TransferService $transferService;
    public TransferResourcesService $transferReourcesService;
    public TransactionService $transactionService;

    public function __construct(
        TransferService $transferService,
        TransferResourcesService $transferReourcesService,
        TransactionService $transactionService
    ) {
        $this->transferService = $transferService;
        $this->transferReourcesService = $transferReourcesService;
        $this->transactionService = $transactionService;
    }
    public function transfer(Request $request, string $account_id): ?JsonResponse
    {
        return $this->transferService->transfer($request, $account_id);
    }

    public function verifyTransferStatusBy(string $account_id): ?JsonResponse
    {
        return $this->transferReourcesService->verifyTransferStatusBy($account_id);
    }

    public function getBanks(Request $request, string $account_id)
    {
        return $this->transferReourcesService->getBanks($request, $account_id);
    }

    public function resolveAccountNumber(Request $request, string $account_id)
    {
        return $this->transferReourcesService->resolveAccountNumber($request, $account_id);
    }

    
    public function resolveAccountByIdentity(Request $request)
    {
        return $this->transferReourcesService->resolveAccountByIdentity($request);
    }

    
    public function getTransactions(Request $request)
    {
        return $this->transactionService->getTransactions($request);
    }
}
