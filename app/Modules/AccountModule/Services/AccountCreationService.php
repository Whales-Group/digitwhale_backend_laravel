<?php

namespace App\Modules\AccountModule\Services;

use App\Enums\AccountType;
use App\Enums\Currency;
use App\Enums\ServiceBank;
use App\Enums\ServiceProvider;
use App\Helpers\CodeHelper;
use App\Helpers\ResponseHelper;
use App\Exceptions\AppException;
use App\Models\Account;
use App\Models\User;

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
            $provider = ServiceProvider::FINCRA;
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
     * @throws AppException
     */
    public function getProviderResponse(ServiceProvider $provider, Currency $currency): array
    {
        return match ($provider) {
            ServiceProvider::PAYSTACK => GatewayResponseService::getPaystackResponse($currency),
            ServiceProvider::FINCRA => GatewayResponseService::getFincraResponse($currency),
            ServiceProvider::FLUTTERWAVE => GatewayResponseService::getFlutterWaveResponse($currency),
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
