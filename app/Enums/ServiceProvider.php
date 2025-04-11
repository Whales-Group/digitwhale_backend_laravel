<?php

namespace App\Enums;

enum ServiceProvider: string
{
    case PAYSTACK = 'PAYSTACK';
    case FINCRA = 'FINCRA';
    case FLUTTERWAVE = 'FLUTTERWAVE';
}