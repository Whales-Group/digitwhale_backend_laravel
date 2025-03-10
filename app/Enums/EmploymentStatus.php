<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case EMPLOYED = 'Employed';
    case UNEMPLOYED = 'Unemployed';
    case STUDENT = 'Student';
    case RETIRED = 'Retired';
}
