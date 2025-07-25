<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Modules\TransferModule\TransferModuleMain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{

    public TransferModuleMain $moduleMain;

    public function __construct(
        TransferModuleMain $moduleMain,
    ) {
        $this->moduleMain = $moduleMain;
    }

    public function transfer(Request $request, string $account_id): ?JsonResponse
    {
        return $this->moduleMain->transfer($request, $account_id);
    }

    public function verifyTransferStatusBy(string $account_id): ?JsonResponse
    {
        return $this->moduleMain->verifyTransferStatusBy($account_id);
        // return ResponseHelper::error();
    }

    public function getBanks(Request $request, string $account_id): ?JsonResponse
    {
        return $this->moduleMain->getBanks($request, $account_id);
    }

    public function resolveAccount(Request $request, string $account_id): ?JsonResponse
    {
        return $this->moduleMain->resolveAccountNumber($request, $account_id);
    }

     public function resolveAccountByIdentity(Request $request): ?JsonResponse
    {
        return $this->moduleMain->resolveAccountByIdentity($request);
    }

    public function getTransactions(Request $request): ?JsonResponse
    {
        return $this->moduleMain->getTransactions($request);
    }

    public function validateTransfer(): ?JsonResponse
    {
        return $this->moduleMain->validateTransfer();
    }
}
