<?php

namespace App\Dtos\PaystackDtos\CountryDto;

class Country
{
    public int $id;
    public string $name;
    public string $iso_code;
    public string $default_currency_code;
    public array $integration_defaults;
    public array $relationships; // Relationships data
}
