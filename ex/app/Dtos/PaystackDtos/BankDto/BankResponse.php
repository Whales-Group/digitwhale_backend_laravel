<?php

namespace App\Dtos\PaystackDtos\BankDto;

class BankResponse
{
    public bool $status;
    public string $message;
    public array $data;
    public array $meta;
}
