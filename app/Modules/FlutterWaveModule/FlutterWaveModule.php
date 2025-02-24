<?php

namespace App\Modules\FlutterWaveModule;

use App\Modules\FlutterWaveModule\Handlers\BaseHandler;
use Illuminate\Http\Request;

class FlutterWaveModule
{
    public BaseHandler $baseHandler;

    public function __construct(
        BaseHandler $baseHandler,
    ) {
        $this->baseHandler = $baseHandler;
    }
    public function handleWebhook(Request $request)
    {
        return $this->baseHandler->handle($request);
    }
}
