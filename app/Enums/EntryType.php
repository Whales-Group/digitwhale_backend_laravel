<?php

namespace App\Enums;


enum EntryType: string
{
    case CREDIT = 'CREDIT';
    case DEBIT = 'DEBIT';
    case REVERSAL = 'REVERSAL';
}