<?php

namespace App\Enums;

enum TransactionChannel: string
{
    case DEDICATED_NUBAN = 'dedicated_nuban';
    case CARD = 'card';
    case BANK = 'bank';
    case TRANSFER = 'transfer';
}
