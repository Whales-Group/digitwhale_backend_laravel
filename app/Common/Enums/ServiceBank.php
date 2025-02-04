<?php

namespace App\Common\Enums;

enum ServiceBank: string
{
    case WEMA_BANK = 'WEMA_BANK';
    case GLOBUS_BANK = 'GLOBUS_BANK';
    case PAYSTACK_TITIAN = 'PAYSTACK_TITIAN';
}