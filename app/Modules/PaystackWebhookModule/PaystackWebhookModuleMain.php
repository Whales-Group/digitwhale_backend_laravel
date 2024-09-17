<?php

namespace App\Modules\PaystackWebhookModule;

use App\Common\Helpers\ResponseHelper;
use App\Common\Enums\PaystackWebhookEvent;
use App\Modules\PaystackWebhookModule\Services\HandleChargeSuccess;
use App\Modules\PaystackWebhookModule\Services\HandleCustomerIdentificationSuccess;
use App\Modules\PaystackWebhookModule\Services\HandleCustomerIdentificationFailed;
use App\Modules\PaystackWebhookModule\Services\HandleDedicatedAccountAssignSuccess;
use App\Modules\PaystackWebhookModule\Services\HandleDedicatedAccountAssignFailed;
use App\Modules\PaystackWebhookModule\Services\HandleTransferSuccess;
use App\Modules\PaystackWebhookModule\Services\HandleTransferFailed;
use App\Modules\PaystackWebhookModule\Services\HandleTransferReversed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class PaystackWebhookModuleMain
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
