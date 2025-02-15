<?php

namespace App\Modules\AccountModule\Services;

use App\Common\Enums\AccountType;
use App\Common\Enums\Currency;
use App\Common\Enums\ServiceBank;
use App\Common\Enums\ServiceProvider;
use App\Common\Helpers\CodeHelper;
use App\Common\Helpers\DateHelper;
use App\Common\Helpers\ResponseHelper;
use App\Exceptions\AppException;
use App\Models\Account;
use App\Models\User;
use App\Modules\FincraModule\Services\FincraService;
use App\Modules\PaystackModule\Services\PaystackService;
use Exception;
use Illuminate\Http\Client\Request;
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

            if (!$user->profileIsCompleted()) {
                throw new AppException("Profile not completed. Update profile to proceed.");
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

            $provider = ServiceProvider::FINCRA;
            $providerBank = ServiceBank::WEMA_BANK;

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
            return ResponseHelper::error(
                message: ResponseHelper::implodeNestedArrays($e->e(), [
                    "email",
                ])
            );
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