<?php
namespace App\Dtos\PaystackDtos\WebHookDtos;

use App\Common\Enums\PaystackEventType;

class TransferWebhookResponse
{
    public PaystackEventType $event;
    public TransferData $data;
}
