<?php

namespace App\Gateways\FlutterWave;

use App\Gateways\FlutterWave\Handlers\BaseHandler;
use Illuminate\Http\Request;

class FlutterWaveModule
{
    public BaseHandler $baseHandler;

    public function __construct(
        BaseHandler $baseHandler,
    ) {
        $this->baseHandler = $baseHandler;
    }
    public function handleWebhook(Request $request): ?\Illuminate\Http\JsonResponse
    {
        return $this->baseHandler->handle($request);
    }
}
