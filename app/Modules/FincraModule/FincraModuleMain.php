<?php

namespace App\Modules\FincraModule;

use App\Modules\FincraModule\Handlers\BaseHandler;
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
