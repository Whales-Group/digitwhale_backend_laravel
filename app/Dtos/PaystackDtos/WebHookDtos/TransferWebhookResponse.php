<?php
namespace App\Dtos\PaystackDtos\WebHookDtos;

use App\Enums\PaystackEventType;

class TransferWebhookResponse
{
    public PaystackEventType $event;
    public TransferData $data;
}
