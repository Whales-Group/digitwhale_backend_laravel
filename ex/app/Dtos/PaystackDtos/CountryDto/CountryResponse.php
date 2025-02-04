<?php

namespace App\Dtos\PaystackDtos\CountryDto;

class CountryResponse
{
    public bool $status;
    public string $message;
    public array $data; // Array of Country objects
}
