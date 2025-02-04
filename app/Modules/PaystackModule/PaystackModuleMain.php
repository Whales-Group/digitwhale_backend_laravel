<?php

namespace App\Modules\PaystackModule;

use App\Modules\PaystackModule\Handlers\BaseHandler;
use GuzzleHttp\Psr7\Request;

class PaystackModuleMain
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
