<?php

namespace App\Http\Controllers;

use App\Modules\WhaleGPTModule\Services\PackageService;

class PackageController extends Controller
{
    protected $packageService;

    public function __construct(PackageService $packageService)
    {
        $this->packageService = $packageService;
    }

    public function index()
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