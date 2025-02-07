<?php

namespace App\Modules\TransferModule;

use App\Modules\TransferModule\Services\TransferResourcesService;
use App\Modules\TransferModule\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class TransferModuleMain
{
    public TransferService $transferService;
    public TransferResourcesService $transferReourcesService;

    public function __construct(
        TransferService $transferService,
        TransferResourcesService $transferReourcesService
    ) {
        $this->transferService = $transferService;
        $this->transferReourcesService = $transferReourcesService;
    }
    public function transfer(Request $request, string $account_id): ?JsonResponse
    {
        return $this->transferService->transfer($request, $account_id);
    }

    public function getBanks(Request $request, string $account_id)
    {
        return $this->transferReourcesService->getBanks($request, $account_id);
    }

    public function resolveAccountNumber(Request $request, string $account_id)
    {
        return $this->transferReourcesService->resolveAccountNumber($request, $account_id);
    }
}
