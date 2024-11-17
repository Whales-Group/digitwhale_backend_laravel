<?php
namespace App\Modules\AccountModule\Services;

use App\Models\Account;

class GetAccountService
{
    public static function getAccount(): Account
    {
        $user_id = auth()->id();
        $account = Account::where("user_id", $user_id)->first();

        return $account;
    }
}
