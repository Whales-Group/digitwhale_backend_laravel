<?php

namespace App\Common\Enums;

enum TransactionType: string
{
    case TRANSFER = 'TRANSFER';
    case DEPOSIT = 'DEPOSIT';
    case WITHDRAWAL = 'WITHDRAWAL';
}