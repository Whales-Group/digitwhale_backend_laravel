<?php

namespace App\Modules\UtilsModule;

use App\Modules\TransferModule\Services\TransactionService;
use App\Modules\TransferModule\Services\TransferResourcesService;
use App\Modules\TransferModule\Services\TransferService;
use App\Modules\UtilsModule\Services\HomeCardService;
use App\Modules\UtilsModule\Services\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class UtilsModuleMain
{
    public TransferService $transferService;
    public TransferResourcesService $transferReourcesService;
    public TransactionService $transactionService;
    public HomeCardService $homeCardService;

    public PackageService $packageService;

    public function __construct(
        TransferService $transferService,
        TransferResourcesService $transferReourcesService,
        TransactionService $transactionService,
        HomeCardService $homeCardService,
        PackageService $packageService

    ) {
        $this->transferService = $transferService;
        $this->transferReourcesService = $transferReourcesService;
        $this->transactionService = $transactionService;
        $this->homeCardService = $homeCardService;
        $this->packageService = $packageService;

    }
    public function getTips()
    {
        return $this->homeCardService->getTips();
    }

    public function getPackages()
    {
        return $this->packageService->getPackages();
    }

    public function subscribe($packageType)
    {
        return $this->packageService->subscribe($packageType);
    }

    public function unsubscribe()
    {
        return $this->packageService->unsubscribe();
    }

    public function upgrade($newPackageType)
    {
        return $this->packageService->upgrade($newPackageType);
    }

    public function downgrade($newPackageType)
    {
        return $this->packageService->downgrade($newPackageType);
    }
}
