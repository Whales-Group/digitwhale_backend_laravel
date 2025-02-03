<?php

namespace App\Common\Enums;


enum EntryType: string
{
    case CREDIT = 'CREDIT';
    case DEBIT = 'DEBIT';
    case REVERSAL = 'REVERSAL';
}