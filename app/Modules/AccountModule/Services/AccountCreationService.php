<?php

namespace App\Modules\AccountModule\Services;

use App\Enums\AccountType;
use App\Enums\Currency;
use App\Enums\ServiceBank;
use App\Enums\ServiceProvider;
use App\Helpers\CodeHelper;
use App\Helpers\DateHelper;
use App\Helpers\ResponseHelper;
use App\Exceptions\AppException;
use App\Models\Account;
use App\Models\User;
use App\Gateways\Fincra\Services\FincraService;
use App\Gateways\Paystack\Services\PaystackService;
use App\Gateways\FlutterWave\Services\FlutterWaveService;

use Exception;
use Illuminate\Support\Facades\DB;
use ValueError;


class AccountCreationService
{
    /**
     * Create an account based on the selected currency and service provider.
     *
     * @return mixed
     */
    public function createAccount(): mixed
    {
        try {

            $user = auth()->user();
            $currencyValue = request()->input('currency');
            $provider = ServiceProvider::FLUTTERWAVE;
            $providerBank = ServiceBank::WEMA_BANK;

            $completed = $user->profileIsCompleted($provider);
            if (!$completed['bool']) {
                throw new AppException($completed['message']);
            }

            // Validate the currency
            if (empty($currencyValue)) {
                throw new AppException("Currency is required.");
            }

            try {
                $currency = Currency::tryFrom($currencyValue);
            } catch (ValueError $e) {
                throw new AppException("Invalid Currency.");
            }

            if ($currency !== Currency::NAIRA) {
                throw new AppException(" Specified currency not avaliable or coming soon.");
            }

            if (Account::where(["user_id" => $user->id, "currency" => $currency])->exists()) {
                throw new AppException("Account with specified currency already exists.");
            }

            $providerResponse = $this->getProviderResponse($provider, $currency);

            DB::beginTransaction();
            $account = match ($currency) {
                Currency::NAIRA => $this->buildNairaAccount($user, $providerBank, $providerResponse),
                Currency::UNITED_STATES_DOLLARS => $this->buildUSDAccount($user, $providerBank, $providerResponse),
                Currency::PAKISTANI_RUPEE => $this->buildRSAccount($user, $providerBank, $providerResponse),
                Currency::GPB => $this->buildGPBAccount($user, $providerBank, $providerResponse),
                default => throw new AppException("Unsupported Currency."),
            };

            $account->save();

            DB::commit();
            return ResponseHelper::success($account);
        } catch (Exception $e) {

            DB::rollBack();
            return ResponseHelper::error($e->getMessage());

        }
    }

    /**
     * Get provider-specific response based on the service provider.
     *
     * @param ServiceProvider $provider
     * @return array
     */
    public function getProviderResponse(ServiceProvider $provider, Currency $currency): array
    {
        return match ($provider) {
            ServiceProvider::PAYSTACK => $this->getPaystackResponse($currency),
            ServiceProvider::FINCRA => $this->getFincraResponse($currency),
            ServiceProvider::FLUTTERWAVE => $this->getFlutterWaveResponse($currency),
            default => throw new AppException("Invalid Service Provider."),
        };
    }

    /**
     * Get Paystack-specific response.
     *
     * @return array
     */
    private function getPaystackResponse(Currency $currency): array
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
    private function getFincraResponse(Currency $currency): array
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
     */
    private function getFlutterWaveResponse(Currency $currency): array
    {
        $user = request()->user();

        $flutterWave = FlutterWaveService::getInstance();

        if (!$user->bvn) {
            throw new AppException("BVN not found. Update bvn and try again.");
        }

        $currencyValue = "";

        switch ($currency) {
            case Currency::NAIRA:
                $currencyValue = "NGN";
                break;
            default:
                throw new AppException("Selected Currency Not Supported for Provider FLUTTERWAVE");
        }

        // $flutter_dva = $flutterWave->createDVA(
        //     email: "abraham@flutterwavego.com",
        //     txRef: "apex_tx_ref-002201",
        //     phoneNumber: "08100000000",
        //     firstName: "John",
        //     lastName: "Doe",
        //     narration: "Kids Foundation",
        //     bvn: "1234567890",
        //     isPermanent: true
        // );

        $flutter_dva = $flutterWave->createDVA(
            email: $user->email,
            txRef: CodeHelper::generateSecureReference(),
            phoneNumber: $user->phone_number ?? '',
            firstName: $user->first_name,
            lastName: $user->last_name,
            narration: ($user->profile_type === 'personal' ? "{$user->first_name} {$user->last_name}" : $user->business_name),
            bvn: $user->bvn,
            isPermanent: true
        );

        return [
            "service_provider" => ServiceProvider::FLUTTERWAVE,
            "bank" => $flutter_dva['data']['bank_name'],
            "account_name" => $user->profile_type === 'personal' ? "{$user->first_name} {$user->last_name}" : $user->business_name,
            "account_number" => $flutter_dva['data']['account_number'],
            "currency" => $currencyValue,
            "customer_code" => $flutter_dva['data']['flw_ref'],
            "customer_id" => $flutter_dva['data']['order_ref'],
            'dedicated_account_id' => $flutter_dva['data']['order_ref'],
            "phone" => $user->phone_number
        ];
    }

    /**
     * Build Naira account details.
     *
     * @param User $user
     * @param ServiceBank $providerBank
     * @param array $providerResponse
     * @return Account
     */
    private function buildNairaAccount(User $user, ServiceBank $providerBank, array $providerResponse): Account
    {
        return $this->createAccountModel($user, Currency::NAIRA, $providerBank, $providerResponse);
    }

    /**
     * Build USD account details.
     *
     * @param User $user
     * @param ServiceBank $providerBank
     * @param array $providerResponse
     * @return Account
     */
    private function buildUSDAccount(User $user, ServiceBank $providerBank, array $providerResponse): Account
    {
        return $this->createAccountModel($user, Currency::UNITED_STATES_DOLLARS, $providerBank, $providerResponse);
    }

    /**
     * Build Pakistani Rupee account details.
     *
     * @param User $user
     * @param ServiceBank $providerBank
     * @param array $providerResponse
     * @return Account
     */
    private function buildRSAccount(User $user, ServiceBank $providerBank, array $providerResponse): Account
    {
        return $this->createAccountModel($user, Currency::PAKISTANI_RUPEE, $providerBank, $providerResponse);
    }

    /**
     * Build GBP account details.
     *
     * @param User $user
     * @param ServiceBank $providerBank
     * @param array $providerResponse
     * @return Account
     */
    private function buildGPBAccount(User $user, ServiceBank $providerBank, array $providerResponse): Account
    {
        return $this->createAccountModel($user, Currency::GPB, $providerBank, $providerResponse);
    }

    /**
     * Create an account model with common attributes.
     *
     * @param User $user
     * @param Currency $currency
     * @param ServiceBank $providerBank
     * @param array $providerResponse
     * @return Account
     */
    private function createAccountModel(User $user, Currency $currency, ServiceBank $providerBank, array $providerResponse): Account
    {
        return Account::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'tag' => $user->tag,
            'account_id' => CodeHelper::generate(20),
            'balance' => "0",
            'account_type' => AccountType::TYPE_1,
            'currency' => $currency,
            'validated_name' => $providerResponse['account_name'] ?? null,
            'blacklisted' => false,
            'enabled' => true,
            'intrest_rate' => 6,
            'max_balance' => 50000,
            'daily_transaction_limit' => 50000,
            'daily_transaction_count' => 0,
            'pnd' => false,
            'dedicated_account_id' => $providerResponse['dedicated_account_id'] ?? null,
            'account_number' => $providerResponse['account_number'] ?? null,
            'customer_id' => $providerResponse['customer_id'] ?? null,
            'customer_code' => $providerResponse['customer_code'] ?? null,
            'service_provider' => $providerResponse['service_provider'] ?? null,
            'service_bank' => $providerResponse['bank'] ?? null,
        ]);

    }

}