<?php

namespace App\Enums;

enum PaystackWebhookEvent: string
{
    case CUSTOMER_IDENTIFICATION_SUCCESS = "customeridentification.success";
    case CUSTOMER_IDENTIFICATION_FAILED = "customeridentification.failed";
    case DEDICATED_ACCOUNT_ASSIGN_SUCCESS = "dedicatedaccount.assign.success";
    case DEDICATED_ACCOUNT_ASSIGN_FAILED = "dedicatedaccount.assign.failed";
    case CHARGE_SUCCESS = "charge.success";
    case TRANSFER_SUCCESS = "transfer.success";
    case TRANSFER_FAILED = "transfer.failed";
    case TRANSFER_REVERSED = "transfer.reversed";
}
