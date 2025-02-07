<?php

namespace App\Modules\FincraModule\Handlers;

use App\Common\Enums\FincraWebhookEvent;
use App\Common\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BaseHandler
{
    public function handle(Request $request): ?JsonResponse
    {
        $merchantWebhookSecretKey = '25d3de0a45824666bf2439ed2e5787f1'/*env('FINCRA_WEBHOOK_SECRET')*/ ;
        $data = $request->all();
        $signatureFromWebhook = $request->header('signature');

        // if (!$this->isValidSignature($data, $signatureFromWebhook, $merchantWebhookSecretKey)) {
        //     Log::warning("Invalid webhook signature", ["event" => $data['event']]);
        //     return ResponseHelper::unauthorized("Invalid webhook signature");
        // }

        $event = $data['event'] ?? null;
        $transactionData = $data['data'] ?? [];

        try {
            $eventEnum = FincraWebhookEvent::from($event);
        } catch (\ValueError $e) {
            Log::warning("Unhandled webhook event", ["event" => $event]);
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

    private function isValidSignature(array $payload, ?string $signature, string $secretKey): bool
    {
        if (!$signature) {
            return false;
        }

        $computedSignature = hash_hmac('sha512', json_encode($payload), $secretKey);
        return hash_equals($computedSignature, $signature);
    }
}