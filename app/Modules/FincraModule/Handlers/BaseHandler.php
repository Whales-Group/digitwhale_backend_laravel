<?php

namespace App\Modules\FincraModule\Handlers;

use App\Common\Enums\FincraWebhookEvent;
use App\Common\Helpers\ResponseHelper;
use App\Modules\FincraWebhookModule\Services\HandleTransferFailed;
use App\Modules\FincraWebhookModule\Services\HandleTransferSuccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaseHandler
{
    public function handle(Request $request): ?JsonResponse
    {
        $data = $request->all();

        $event = $request->input("event");
        $transactionData = $data['data'];


        try {
            $eventEnum = FincraWebhookEvent::from($event);
        } catch (\ValueError $e) {
            // Log::warning("Unhandled webhook event", ["event" => $event]);
            return ResponseHelper::unprocessableEntity(
                message: "Unhandled webhook event",
                error: ["event" => $event]
            );
        }

        return match ($eventEnum) {
            FincraWebhookEvent::TRANSFER_SUCCESS => \App\Modules\FincraModule\Handlers\HandleTransferSuccess::handle($transactionData),
            FincraWebhookEvent::TRANSFER_FAILED => \App\Modules\FincraModule\Handlers\HandleTransferFailed::handle($transactionData),
            default => ResponseHelper::unprocessableEntity(
                message: "Unhandled webhook event",
                error: ["event" => $event]
            ),
        };
    }
}
