<?php

namespace App\Common\Enums;

enum AnnualIncome: string
{
    case BELOW_100K = 'Below100k';
    case BETWEEN_100K_AND_500K = 'Between100kAnd500k';
    case ABOVE_500K = 'Above500k';
}
