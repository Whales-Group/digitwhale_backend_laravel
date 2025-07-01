<?php

namespace App\Modules\AccountModule\Services;

use App\Enums\Currency;
use App\Enums\ServiceProvider;
use App\Exceptions\AppException;
use App\Gateways\Fincra\Services\FincraService;
use App\Gateways\FlutterWave\Services\FlutterWaveService;
use App\Gateways\Paystack\Services\PaystackService;
use App\Helpers\CodeHelper;
use App\Helpers\DateHelper;
use App\Helpers\ResponseHelper;

class GatewayResponseService
{


    /**
     * Get Paystack-specific response.
     *
     * @return array
     */
    static  public function getPaystackResponse(Currency $currency): array
    {
        $user = request()->user();

        $paystack = PaystackService::getInstance();

        $customer = $paystack->createCustomer([
            'email' => $user->email,
            'first_name' => $user->first_name ?? null,
            'last_name' => $user->last_name ?? null,
            'phone' => $user->phone_number ?? null,
        ]);

        $paystack_dva = $paystack->createDVA(customer: $customer['data']['customer_code'], phone: $user->phone_number ?? null);

        return [
            "service_provider" => ServiceProvider::PAYSTACK,
            "bank" => $paystack_dva['data']['bank']['name'],
            "account_name" => $paystack_dva['data']['account_name'],
            "account_number" => $paystack_dva['data']['account_number'],
            "currency" => $paystack_dva['data']['currency'],
            "customer_code" => $paystack_dva['data']['customer']['customer_code'],
            "customer_id" => $paystack_dva['data']['customer']['id'],
            'dedicated_account_id' => $paystack_dva['data']['id'],
            "phone" => $paystack_dva['data']['customer']['phone']
        ];
    }

    /**
     * Get Fincra-specific response.
     *
     * @return array
     */
    static  public function getFincraResponse(Currency $currency): array
    {
        $user = request()->user();

        $fincra = FincraService::getInstance();

        if (!$user->bvn) {
            throw new AppException("BVN not found. Update bvn and try again.");
        }

        $currencyValue = "";

        switch ($currency) {
            case Currency::NAIRA:
                $currencyValue = "NGN";
                break;
            default:
                throw new AppException("Selected Currency Not Supported for Provider FINCRA");
        }

        $fincra_dva = $fincra->createDVA(
            dateOfBirth: DateHelper::format($user->date_of_birth, "m-d-Y"),
            firstName: $user->first_name,
            lastName: $user->last_name,
            bvn: $user->bvn,
            bank: "wema",
            currency: $currencyValue,
            email: $user->email
        );

        return [
            "service_provider" => ServiceProvider::FINCRA,
            "bank" => $fincra_dva['data']['accountInformation']['bankName'],
            "account_name" => $fincra_dva['data']['accountInformation']['accountName'],
            "account_number" => $fincra_dva['data']['accountInformation']['accountNumber'],
            "currency" => $fincra_dva['data']['currency'],
            "customer_code" => $fincra_dva['data']['accountNumber'],
            "customer_id" => $fincra_dva['data']['_id'],
            'dedicated_account_id' => $fincra_dva['data']['_id'],
            "phone" => $user->phone_number
        ];
    }

    /**
     * Get FlutterWaveService-specific response.
     *
     * @return array
     * @throws AppException
     */
    static public function getFlutterWaveResponse(Currency $currency): mixed
    {
        $user = request()->user();

        $flutterWave = FlutterWaveService::getInstance();

        if (!$user->bvn) {
            throw new AppException("BVN not found. Update bvn and try again.");
        }

        $currencyValue = match ($currency) {
            Currency::NAIRA => "NGN",
            default => throw new AppException("Selected Currency Not Supported for Provider FLUTTERWAVE"),
        };

//         $flutter_dva = $flutterWave->createDVA(
//             email: "abraham@flutterwavego.com",
//             txRef: "apex_tx_ref-002201",
//             phoneNumber: "08100000000",
//             firstName: "John",
//             lastName: "Doe",
//             narration: "Kids Foundation",
//             bvn: "12345678901",
//             isPermanent: true
//         );


        $flutter_dva = $flutterWave->createDVA(
            email: $user->email,
            txRef: CodeHelper::generateSecureReference(),
            phoneNumber: $user->phone_number ?? '',
            firstName: $user->profile_type === 'personal' ? $user->first_name : $user->business_name,
            lastName: $user->profile_type === 'personal' ? $user->last_name : "",
            narration: ($user->profile_type === 'personal' ? "{$user->first_name} {$user->last_name}" : $user->business_name),
            bvn: $user->bvn
        );

        return [
            "service_provider" => ServiceProvider::FLUTTERWAVE,
            "bank" => $flutter_dva['data']['bank_name'],
            "account_name" => $user->profile_type === 'personal' ? "{$user->first_name} {$user->last_name} FLW" : $user->business_name . " FLW",
            "account_number" => $flutter_dva['data']['account_number'],
            "currency" => $currencyValue,
            "customer_code" => $flutter_dva['data']['flw_ref'],
            "customer_id" => $flutter_dva['data']['order_ref'],
            'dedicated_account_id' => $flutter_dva['data']['order_ref'],
            "phone" => $user->phone_number
        ];
    }
}
