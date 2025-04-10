<?php

namespace App\Gateways\Fincra;

use App\Gateways\Fincra\Handlers\BaseHandler;
use Illuminate\Http\Request;

class FincraModuleMain
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
