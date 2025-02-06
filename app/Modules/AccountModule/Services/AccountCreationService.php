<?php

namespace App\Modules\AccountModule\Services;

use App\Common\Enums\AccountType;
use App\Common\Enums\Currency;
use App\Common\Enums\ServiceBank;
use App\Common\Enums\ServiceProvider;
use App\Common\Helpers\CodeHelper;
use App\Common\Helpers\ResponseHelper;
use App\Exceptions\AppException;
use App\Models\Account;
use App\Models\User;
use App\Modules\PaystackModule\Services\PaystackService;
use Illuminate\Http\Request;
use ValueError;


class AccountCreationService
{
    /**
     * Create an account based on the selected currency and service provider.
     *
     * @return mixed
     */
    public function createAccount(Request $request): mixed
    {
        $user = auth()->user();
        $currencyValue = $request['currency'];

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
            throw new AppException($currency->value ?? $currencyValue . " Implementation doesn't exists");
        }

        if (Account::where(["user_id" => $user->id, "currency" => $currency])->exists()) {
            throw new AppException("{$currencyValue} Account Already Exists.");
        }

        $provider = ServiceProvider::FINCRA;
        $providerBank = ServiceBank::WEMA_BANK;

        $providerResponse = $this->getProviderResponse($provider);

        $account = match ($currency) {
            Currency::NAIRA => $this->buildNairaAccount($user, $providerBank, $providerResponse),
            Currency::UNITED_STATES_DOLLARS => $this->buildUSDAccount($user, $providerBank, $providerResponse),
            Currency::PAKISTANI_RUPEE => $this->buildRSAccount($user, $providerBank, $providerResponse),
            Currency::GPB => $this->buildGPBAccount($user, $providerBank, $providerResponse),
            default => throw new AppException("Unsupported Currency."),
        };

        $account->save();

        return ResponseHelper::success($account);
    }

    /**
     * Get Paystack-specific response.
     *
     * @return array
     */
    private function getPaystackResponse(): array
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
    private function getFincraResponse(): array
    {
        $fincra_dva = [
            'data' => [
                'bank' => [
                    'name' => 'Dummy Bank Name',
                ],
                'account_name' => 'Dummy Account Name',
                'account_number' => '1234567890',
                'currency' => 'NGN',
                'customer' => [
                    'customer_code' => 'CUS123456',
                    'id' => 123456,
                    'phone' => '08012345678',
                ],
                'id' => 123456,
            ],
        ];

        return [
            "service_provider" => ServiceProvider::FINCRA,
            "bank" => $fincra_dva['data']['bank']['name'],
            "account_name" => $fincra_dva['data']['account_name'],
            "account_number" => $fincra_dva['data']['account_number'],
            "currency" => $fincra_dva['data']['currency'],
            "customer_code" => $fincra_dva['data']['customer']['customer_code'],
            "customer_id" => $fincra_dva['data']['customer']['id'],
            'dedicated_account_id' => $fincra_dva['data']['id'],
            "phone" => $fincra_dva['data']['customer']['phone']
        ];
    }

    /**
     * Get provider-specific response based on the service provider.
     *
     * @param ServiceProvider $provider
     * @return array
     */
    public function getProviderResponse(ServiceProvider $provider): array
    {
        return match ($provider) {
            ServiceProvider::PAYSTACK => $this->getPaystackResponse(),
            ServiceProvider::FINCRA => $this->getFincraResponse(),
            default => throw new AppException("Invalid Service Provider."),
        };
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