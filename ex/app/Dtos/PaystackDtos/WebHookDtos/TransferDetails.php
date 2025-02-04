<?php
namespace App\Dtos\PaystackDtos\WebHookDtos;

class TransferDetails
{
    public string $account_number;
    public ?string $account_name;
    public string $bank_code;
    public string $bank_name;
}
