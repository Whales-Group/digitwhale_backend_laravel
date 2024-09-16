<?php

namespace App\Dtos\PaystackDtos\BankDto;

class Bank
{
    public string $name;
    public string $slug;
    public string $code;
    public string $longcode;
    public ?string $gateway;
    public bool $pay_with_bank;
    public bool $active;
    public bool $is_deleted;
    public string $country;
    public string $currency;
    public string $type;
    public int $id;
    public string $createdAt;
    public string $updatedAt;
}
