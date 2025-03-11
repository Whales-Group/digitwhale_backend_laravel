<?php

namespace App\Http\Controllers;

use App\Modules\AiModule\AiModuleMain;
use App\Modules\PaystackModule\PaystackModuleMain;
use App\Modules\PaystackModule\Services\PaystackService;
use App\Modules\UtilsModule\UtilsModuleMain;

class AiController extends Controller
{
    public AiModuleMain $aiModuleMain;

    public function __construct(
        AiModuleMain $aiModuleMain

    ) {
        $this->aiModuleMain = $aiModuleMain;

    }
    public function conversation()
    {
        return $this->aiModuleMain->processQuery();
    }
}