<?php

namespace App\Enums;

enum VerificationType: string
{
    case NIN = 'NIN';
    case BVN = 'BVN';
    case PASSPORT = 'PASSPORT';
    case DRIVER_LICENSE = 'DRIVER_LICENSE';
    case VOTER_CARD = 'VOTER_CARD';
    case PHONE_NUMBER = 'PHONE_NUMBER';
    case ADDRESS = 'ADDRESS';
}
