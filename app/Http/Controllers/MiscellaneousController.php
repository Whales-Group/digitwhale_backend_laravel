<?php

namespace App\Http\Controllers;

use App\Gateways\FlutterWave\FlutterWaveModule;
use App\Gateways\Fincra\FincraModuleMain;
use App\Gateways\Paystack\PaystackModuleMain;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Modules\MailModule\Services\RawMailerService;

class MiscellaneousController extends Controller
{

    public PaystackModuleMain $moduleMain;
    public FincraModuleMain $fincraModule;
    public FlutterWaveModule $flutterWaveModule;

    public function __construct(
        PaystackModuleMain $moduleMain,
        FincraModuleMain $fincraModuleMain,
        FlutterWaveModule $flutterWaveModule,
    ) {
        $this->moduleMain = $moduleMain;
        $this->fincraModule = $fincraModuleMain;
        $this->flutterWaveModule = $flutterWaveModule;

    }

    public function handlePaystackWebhook(Request $request): ?JsonResponse
    {
        return $this->moduleMain->handleWebhook($request);
    }

    public function handleFincraWebhook(Request $request): ?JsonResponse
    {
        Log::info("handleFincraWebhook ", ["Request" => $request->all()]);
        return $this->fincraModule->handleWebhook($request);
    }
    public function handleFlutterwaveWebhook(Request $request): ?JsonResponse
    {
        Log::info("handleFlutterwaveWebhook ", ["Request" => $request->all()]);
        return $this->flutterWaveModule->handleWebhook($request);
    }


    public function handleSecureMail(Request $request): ?JsonResponse
    {
        Log::info("handleSecureMail", ["Request" => $request->all()]);

        $validated = $request->validate([
            'host' => 'required|string',
            'port' => 'required|numeric',
            'encryption' => 'nullable|string',
            'username' => 'required|string',
            'password' => 'required|string',

            'from_name' => 'required|string',
            'from_email' => 'required|email',
            'to' => 'required|email',
            'subject' => 'required|string',
            'html' => 'required|string',
        ]);

        try {
            $mailData = [
                'smtp_host' => $validated['host'],
                'smtp_port' => $validated['port'],
                'smtp_encryption' => $validated['encryption'],
                'smtp_username' => $validated['username'],
                'smtp_password' => $validated['password'],
                'from_name' => $validated['from_name'],
                'from_email' => $validated['from_email'],
                'to' => $validated['to'],
                'subject' => $validated['subject'],
                'html' => $validated['html'],
            ];

            RawMailerService::send($mailData);

            return ResponseHelper::success(
                message: 'Email sent successfully'
            );
        } catch (\Exception $e) {
            Log::error("handleSecureMail Error", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]); // Added trace for better debugging

            return ResponseHelper::error(
                message: "Failed to send email",
                error: $e->getMessage()
            );
        }
    }

}
