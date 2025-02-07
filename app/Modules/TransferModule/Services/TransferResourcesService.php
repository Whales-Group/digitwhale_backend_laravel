<?php

namespace App\Modules\TransferModule\Services;

use App\Common\Enums\ServiceProvider;
use App\Common\Helpers\ResponseHelper;
use App\Exceptions\AppException;
use App\Models\Account;
use App\Modules\FincraModule\Services\FincraService;
use App\Modules\PaystackModule\Services\PaystackService;
use Illuminate\Http\Request;

class TransferResourcesService
{
    public FincraService $fincraService;
    public PaystackService $paystackService;

    public function __construct()
    {
        $this->fincraService = FincraService::getInstance();
        $this->paystackService = PaystackService::getInstance();
    }

    public function getBanks(Request $request, string $account_id)
    {
        try {
            $user = auth()->user();
            $account = Account::where("user_id", $user->id)->where("account_id", $account_id)->first();

            if(!$account){
                throw new AppException("Invalid account id or account not found.");
            }
            
            try {
                $accountType = ServiceProvider::tryFrom($account->service_provider);
            } catch (AppException $e) {
                throw new AppException("Invalid Account Type");
            }

            switch ($accountType) {
                case ServiceProvider::FINCRA:
                    return ResponseHelper::success($this->fincraService->getBanks()['data']);
                case ServiceProvider::PAYSTACK:
                    return ResponseHelper::success($this->paystackService->getBanks()['data']);
                default:
                    return ResponseHelper::unprocessableEntity("Invalid account service provider.");
            }

        } catch (AppException $e) {
            return ResponseHelper::unprocessableEntity("Failed to get Banks");
        }
    }


    public function resolveAccountNumber(Request $request, string $account_id)
    {
        $bank_code = trim($request->input('bank_code'));
        $account_number = substr(trim($request->input('account_number')), 0, 10);
        try {
            $user = auth()->user();
            $account = Account::where("user_id", $user->id)->where("account_id", $account_id)->first();
            $response = ['accountName' => "", 'accountNumber' => ""];

            if (strlen($account_number) > 10) {
                throw new AppException("accountNumber length must be 10 characters long");
            }

            if(!$account){
                throw new AppException("Invalid account id or account not found.");
            }

            try {
                $serviceProvider = ServiceProvider::tryFrom($account->service_provider);
            } catch (AppException $e) {
                throw new AppException("Invalid Account Type");
            }

            switch ($serviceProvider) {
                case ServiceProvider::FINCRA:
                    $fincra_res = $this->fincraService->resolveAccount($account_number, $bank_code);
                    $response['accountName'] = $fincra_res['data']['accountName'];
                    $response['accountNumber'] = $fincra_res['data']['accountNumber'];

                    return ResponseHelper::success($response);
                case ServiceProvider::PAYSTACK:
                    $paystack_res = $this->paystackService->resolveAccount($account_number, $bank_code);
                    $response['accountName'] = $paystack_res['data']['account_name'];
                    $response['accountNumber'] = $paystack_res['data']['account_number'];

                    return ResponseHelper::success($response);
                default:
                    $response['message'] = "failed to verify account: check and try again.";
                    return ResponseHelper::success($response);

            }

        } catch (AppException $e) {
            
            return ResponseHelper::unprocessableEntity("Failed to resolve Account" . $e->getMessage());
        }
    }

}
