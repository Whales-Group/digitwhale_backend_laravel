<?php

namespace App\Modules\AccountModule\Services;

use App\Common\Helpers\CodeHelper;
use App\Enums\AccountType;
use App\Enums\VerificationType;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\PersonalDetails;
use App\Models\VerificationRecord;
use Illuminate\Http\Request;

class AccountCreationService
{
    public function createAccount(Request $request)
    {

        $newAccount = $this->buildAccount($request);

        $newAccount->save();

        return $newAccount;
    }



    private function buildAccount(array $data)
    {
        $user = request()->user();

        // Create a new account instance
        $newAccount = new Account();


        // Set the account properties
        $newAccount->user_id = $data['user_id'];
        $newAccount->email = $data['email'];
        $newAccount->phone_number = $user->phone_number;
        $newAccount->tag = $user->tag;
        $newAccount->account_id = CodeHelper::generate(20);
        $newAccount->balance = "0";
        $newAccount->account_type = AccountType::TYPE_1;
        $newAccount->currency = $data['currency'];
        $newAccount->validated_name = $data['validated_name'];
        $newAccount->blacklisted = $data['blacklisted'];
        $newAccount->enabled = $data['enabled'];
        $newAccount->intrest_rate = $data['intrest_rate'];
        $newAccount->max_balance = $data['max_balance'];
        $newAccount->daily_transaction_limit = $data['daily_transaction_limit'];
        $newAccount->daily_transaction_count = $data['daily_transaction_count'];
        $newAccount->pnd = $data['pnd'];
        $newAccount->dedicated_account_id = $data['dedicated_account_id'];
        $newAccount->account_number = $data['account_number'];
        $newAccount->customer_id = $data['customer_id'];
        $newAccount->customer_code = $data['customer_code'];
        $newAccount->service_provider = $data['service_provider'];

        return $newAccount;
    }

}