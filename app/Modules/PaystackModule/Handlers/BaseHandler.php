<?php

namespace App\Modules\PaystackModule\Handlers;

use App\Enums\PaystackWebhookEvent;
use App\Helpers\ResponseHelper;
use App\Models\AppLog;
use App\Modules\PaystackModule\Services\HandleChargeSuccess;
use App\Modules\PaystackModule\Services\HandleCustomerIdentificationFailed;
use App\Modules\PaystackModule\Services\HandleCustomerIdentificationSuccess;
use App\Modules\PaystackModule\Services\HandleDedicatedAccountAssignFailed;
use App\Modules\PaystackModule\Services\HandleDedicatedAccountAssignSuccess;
use App\Modules\PaystackModule\Services\HandleTransferFailed;
use App\Modules\PaystackModule\Services\HandleTransferReversed;
use App\Modules\PaystackModule\Services\HandleTransferSuccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaseHandler
{
    public function handle(Request $request): ?JsonResponse
    {
        $event = $request->input("event");

        try {
            $eventEnum = PaystackWebhookEvent::from($event);
        } catch (\ValueError $e) {
            AppLog::warning("Unhandled webhook event", ["event" => $event]);
            return ResponseHelper::unprocessableEntity(
                message: "Unhandled webhook event",
                error: ["event" => $event]
            );
        }

        return match ($eventEnum) {
            PaystackWebhookEvent::CUSTOMER_IDENTIFICATION_SUCCESS => HandleCustomerIdentificationSuccess::handle(),
            PaystackWebhookEvent::CUSTOMER_IDENTIFICATION_FAILED => HandleCustomerIdentificationFailed::handle(),
            PaystackWebhookEvent::DEDICATED_ACCOUNT_ASSIGN_SUCCESS => HandleDedicatedAccountAssignSuccess::handle(),
            PaystackWebhookEvent::DEDICATED_ACCOUNT_ASSIGN_FAILED => HandleDedicatedAccountAssignFailed::handle(),
            PaystackWebhookEvent::CHARGE_SUCCESS => HandleChargeSuccess::handle(),
            PaystackWebhookEvent::TRANSFER_SUCCESS => HandleTransferSuccess::handle(),
            PaystackWebhookEvent::TRANSFER_FAILED => HandleTransferFailed::handle(),
            PaystackWebhookEvent::TRANSFER_REVERSED => HandleTransferReversed::handle(),
            default => ResponseHelper::unprocessableEntity(
                message: "Unhandled webhook event",
                error: ["event" => $event]
            ),
        };
    }
}
