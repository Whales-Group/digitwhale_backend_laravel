<?php

namespace App\Modules\PaystackModule\Handlers;

use App\Common\Enums\PaystackWebhookEvent;
use App\Common\Helpers\ResponseHelper;
use App\Modules\PaystackWebhookModule\Services\HandleChargeSuccess;
use App\Modules\PaystackWebhookModule\Services\HandleCustomerIdentificationFailed;
use App\Modules\PaystackWebhookModule\Services\HandleCustomerIdentificationSuccess;
use App\Modules\PaystackWebhookModule\Services\HandleDedicatedAccountAssignFailed;
use App\Modules\PaystackWebhookModule\Services\HandleDedicatedAccountAssignSuccess;
use App\Modules\PaystackWebhookModule\Services\HandleTransferFailed;
use App\Modules\PaystackWebhookModule\Services\HandleTransferReversed;
use App\Modules\PaystackWebhookModule\Services\HandleTransferSuccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\AppLog;

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
