<?php

namespace App\Enums;

enum TransactionType: string
{
    case TRANSFER = 'TRANSFER';
    case DEPOSIT = 'DEPOSIT';
    case WITHDRAWAL = 'WITHDRAWAL';
}