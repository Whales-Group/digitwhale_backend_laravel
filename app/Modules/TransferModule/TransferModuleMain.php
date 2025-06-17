<?php

namespace App\Modules\TransferModule;

use App\Modules\TransferModule\Services\AirtransferService;
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
    public AirtransferService $airTransferService;

    public function __construct(
        TransferService $transferService,
        TransferResourcesService $transferReourcesService,
        TransactionService $transactionService,
        AirtransferService $airTransferService,
    ) {
        $this->transferService = $transferService;
        $this->transferReourcesService = $transferReourcesService;
        $this->transactionService = $transactionService;
        $this->airTransferService = $airTransferService;
    }

    public function transfer(Request $request, string $account_id): ?JsonResponse
    {
        return $this->transferService->transfer($request, $account_id);
    }

    public function verifyTransferStatusBy(string $account_id): ?JsonResponse
    {
        return $this->transferReourcesService->verifyTransferStatusBy($account_id);
    }

    public function validateTransfer(): ?JsonResponse
    {
        return $this->transferReourcesService->validateTransfer();
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


    public function getNearestUsers(float $latitude, float $longitude, int $limit = 5)
    {
        return $this->airTransferService->getNearestUsers($latitude, $longitude, $limit = 5);
    }

    public function updateLiveLocation(float $latitude, float $longitude)
    {
        return $this->airTransferService->updateLiveLocation($latitude, $longitude);
    }


    public function getAirTransferPreference()
    {
        return $this->airTransferService->getAirTransferPreference();
    }


    public function setAirTransferPreference(array $data)
    {
        return $this->airTransferService->setAirTransferPreference($data);
    }


}
