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
        $event = $request->input("event");

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
            FincraWebhookEvent::TRANSFER_SUCCESS => \App\Modules\FincraModule\Handlers\HandleTransferSuccess::handle(),
            FincraWebhookEvent::TRANSFER_FAILED => \App\Modules\FincraModule\Handlers\HandleTransferFailed::handle(),
            default => ResponseHelper::unprocessableEntity(
                message: "Unhandled webhook event",
                error: ["event" => $event]
            ),
        };
    }
}
