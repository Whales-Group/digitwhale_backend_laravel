<?php

namespace App\Enums;

enum FincraWebhookEvent: string
{
    case TRANSFER_SUCCESS = "collection.successful";
    case TRANSFER_FAILED = "collection.failed";
}
