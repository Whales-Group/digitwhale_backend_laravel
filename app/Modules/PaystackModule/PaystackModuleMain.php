<?php

namespace App\Modules\PaystackModule;

use App\Common\Helpers\ResponseHelper;
use App\Common\Enums\PaystackWebhookEvent;
use App\Modules\PaystackModule\Services\HandleChargeSuccess;
use App\Modules\PaystackModule\Services\HandleCustomerIdentificationSuccess;
use App\Modules\PaystackModule\Services\HandleCustomerIdentificationFailed;
use App\Modules\PaystackModule\Services\HandleDedicatedAccountAssignSuccess;
use App\Modules\PaystackModule\Services\HandleDedicatedAccountAssignFailed;
use App\Modules\PaystackModule\Services\HandleTransferSuccess;
use App\Modules\PaystackModule\Services\HandleTransferFailed;
use App\Modules\PaystackModule\Services\HandleTransferReversed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class PaystackModuleMain
{
    public static function handle(Request $request): ?JsonResponse
    {
        $event = $request->input("event");

        try {
            $eventEnum = PaystackWebhookEvent::from($event);
        } catch (\ValueError $e) {
            Log::warning("Unhandled webhook event", ["event" => $event]);
            return ResponseHelper::unprocessableEntity(
                message: "Unhandled webhook event",
                error: ["event" => $event]
            );
        }

        switch ($eventEnum) {
            case PaystackWebhookEvent::CUSTOMER_IDENTIFICATION_SUCCESS:
                return HandleCustomerIdentificationSuccess::handle();

            case PaystackWebhookEvent::CUSTOMER_IDENTIFICATION_FAILED:
                return HandleCustomerIdentificationFailed::handle();

            case PaystackWebhookEvent::DEDICATED_ACCOUNT_ASSIGN_SUCCESS:
                return HandleDedicatedAccountAssignSuccess::handle();

            case PaystackWebhookEvent::DEDICATED_ACCOUNT_ASSIGN_FAILED:
                return HandleDedicatedAccountAssignFailed::handle();

            case PaystackWebhookEvent::CHARGE_SUCCESS:
                return HandleChargeSuccess::handle();

            case PaystackWebhookEvent::TRANSFER_SUCCESS:
                return HandleTransferSuccess::handle();

            case PaystackWebhookEvent::TRANSFER_FAILED:
                return HandleTransferFailed::handle();

            case PaystackWebhookEvent::TRANSFER_REVERSED:
                return HandleTransferReversed::handle();

            default:
                Log::warning("Unhandled webhook event", ["event" => $event]);
                return ResponseHelper::unprocessableEntity(
                    message: "Unhandled webhook event",
                    error: ["event" => $event]
                );
        }
    }
}
