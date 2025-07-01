<?php

namespace App\Gateways\Paystack\Handlers;

use App\Enums\PaystackWebhookEvent;
use App\Helpers\ResponseHelper;
use App\Gateways\Paystack\Handlers\HandleChargeSuccess;
use App\Gateways\Paystack\Handlers\HandleCustomerIdentificationFailed;
use App\Gateways\Paystack\Handlers\HandleCustomerIdentificationSuccess;
use App\Gateways\Paystack\Handlers\HandleDedicatedAccountAssignFailed;
use App\Gateways\Paystack\Handlers\HandleDedicatedAccountAssignSuccess;
use App\Gateways\Paystack\Handlers\HandleTransferFailed;
use App\Gateways\Paystack\Handlers\HandleTransferReversed;
use App\Gateways\Paystack\Handlers\HandleTransferSuccess;
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
