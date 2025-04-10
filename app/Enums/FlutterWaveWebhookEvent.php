<?php

namespace App\Enums;

enum FlutterWaveWebhookEvent: string
{
    case TRANSFER = "transfer.completed";
    case CHARGE = "charge.completed";
    case SINGLE_BILL_PAYMENT = "singlebillpayment.status";
}
