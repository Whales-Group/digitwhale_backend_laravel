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

class GetAccountService
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
            throw new AppException("$currency->value Implementation doesn't exists");
        }

        if (Account::where(["user_id" => $user->id, "currency" => $currency])->exists()) {
            throw new AppException("$currency->value Account Already Exists.");
        }

        $provider = ServiceProvider::PAYSTACK;
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
     * Retrieve accounts for the authenticated user.
     *
     * @return mixed
     */
    public static function getAccount(): mixed
    {
        $userId = auth()->id();
        $accounts = Account::where('user_id', $userId)->get();
        return ResponseHelper::success($accounts);
    }

    public static function updateAccount(Request $request): mixed
    {
        $userId = auth()->id();

        $accountId = $request->input('account_id');

        $updatableFields = [
            'phone_number',
            'tag',
            'blacklisted',
            'enabled',
            'intrest_rate',
            'max_balance',
            'daily_transaction_limit',
            'daily_transaction_count',
            'pnd',
        ];

        $updateData = $request->only($updatableFields);

        if (empty($accountId)) {
            throw new AppException("Account ID must be provided.");
        }

        if (empty($updateData)) {
            throw new AppException("No valid fields provided for update.");
        }

        $account = Account::where('user_id', $userId)
            ->where('account_id', $accountId)
            ->first();

        if (!$account) {
            throw new AppException("Account not found for the specified ID.");
        }

        if ($account->updateAccount($updateData)) {
            return ResponseHelper::success($account, "Account updated successfully.");
        }

        throw new AppException("Failed to update the account.");
    }

    /**
     * Retrieve account details for a specific currency.
     *
     * @param Request $request
     * @return Account
     */
    public static function getAccountDetails(Request $request): mixed
    {
        $userId = auth()->id();

        $accountId = $request->query('account_id');
        $currencyValue = $request->query('currency');

        if (empty($accountId) && empty($currencyValue)) {
            throw new AppException("At least one of 'account_id' or 'currency' must be provided.");
        }

        if (!empty($currencyValue)) {
            $currency = Currency::tryFrom($currencyValue);

            if (!$currency) {
                throw new AppException("Invalid Currency.");
            }
        }

        $query = Account::where('user_id', $userId);

        if (!empty($accountId)) {
            $query->where('account_id', $accountId);
        }

        if (!empty($currencyValue)) {
            $query->where('currency', $currency->name);
        }

        $account = $query->first();

        if (!$account) {
            throw new AppException("Account not found for the specified criteria.");
        }

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
        return [
            "accountNumber" => "3992219528",
            "accountName" => "Fincra Customer's full name",
            "bankName" => "Fincra BANK",
            "currency" => "NGN",
            "customer_code" => '',
            "customer_id" => '',
            "phone" => ''
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
            'max_balance' => 200000,
            'daily_transaction_limit' => 300000,
            'daily_transaction_count' => 0,
            'pnd' => false,
            'dedicated_account_id' => $providerResponse['dedicated_account_id'] ?? null,
            'account_number' => $providerResponse['account_number'] ?? null,
            'customer_id' => $providerResponse['customer_id'] ?? null,
            'customer_code' => $providerResponse['customer_code'] ?? null,
            'service_provider' => ServiceProvider::PAYSTACK,
            'service_bank' => $providerResponse['bank'] ?? null,
        ]);

    }
}
