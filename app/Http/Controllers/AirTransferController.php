<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Modules\TransferModule\TransferModuleMain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AirTransferController extends Controller
{

    public TransferModuleMain $moduleMain;

    public function __construct(
        TransferModuleMain $moduleMain,
    ) {
        $this->moduleMain = $moduleMain;
    }


    public function getNearestUsers(Request $request): JsonResponse
    {
        $latitude = (float) $request->input('latitude');
        $longitude = (float) $request->input('longitude');
        $limit = (int) $request->input('limit', 5);

        $result = $this->moduleMain->getNearestUsers($latitude, $longitude, $limit);
        return $result;
    }

    public function updateLiveLocation(Request $request): JsonResponse
    {
        $latitude = (float) $request->input('latitude');
        $longitude = (float) $request->input('longitude');

        $result = $this->moduleMain->updateLiveLocation($latitude, $longitude);
        return $result;
    }

    public function getAirTransferPreference(): JsonResponse
    {
        return $this->moduleMain->getAirTransferPreference();
    }

    public function setAirTransferPreference(Request $request): JsonResponse
    {
        $data = $request->all();
        $result = $this->moduleMain->setAirTransferPreference($data);
        return $result;
    }


}
