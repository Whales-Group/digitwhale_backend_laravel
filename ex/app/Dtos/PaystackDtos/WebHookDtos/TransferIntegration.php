<?php

namespace App\Dtos\PaystackDtos\WebHookDtos;

class TransferIntegration
{
    public int $id;
    public bool $is_live;
    public string $business_name;
}
