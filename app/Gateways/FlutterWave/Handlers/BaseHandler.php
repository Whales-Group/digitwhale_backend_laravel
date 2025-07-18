<?php

namespace App\Gateways\FlutterWave\Handlers;

use App\Enums\FlutterWaveWebhookEvent;
use App\Helpers\ResponseHelper;
use App\Models\AppLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BaseHandler
{
    public function handle(Request $request): ?JsonResponse
    {
        AppLog::info("BaseHandler ", ["Request" => $request->all()]);
        $merchantWebhookSecretKey = '25d3de0a45824666bf2439ed2e5787f1'/*env('FINCRA_WEBHOOK_SECRET')*/ ;
        $data = $request->all();
        $signatureFromWebhook = $request->header('signature');

        // if (!$this->isValidSignature($data, $signatureFromWebhook, $merchantWebhookSecretKey)) {
        //     Log::warning("Invalid webhook signature", ["event" => $data['event']]);
        //     return ResponseHelper::unauthorized("Invalid webhook signature");
        // }

        $event = $data["event"] ?? null;
        $transactionData = $data['data'] ?? [];

        try {
            $eventEnum = FlutterWaveWebhookEvent::tryFrom($event);
        } catch (\ValueError $e) {
            Log::warning("Unhandled webhook event", ["event" => $event]);
            return ResponseHelper::unprocessableEntity(
                message: "Unhandled webhook event",
                error: ["event" => $event]
            );
        }

        return match ($eventEnum) {
            FlutterWaveWebhookEvent::TRANSFER => \App\Gateways\FlutterWave\Handlers\HandleTransfer::handle($transactionData),
            FlutterWaveWebhookEvent::CHARGE => \App\Gateways\FlutterWave\Handlers\HandleCollection::handle($transactionData),
            FlutterWaveWebhookEvent::SINGLE_BILL_PAYMENT => \App\Gateways\FlutterWave\Handlers\HandleBillPayment::handle($transactionData),
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
