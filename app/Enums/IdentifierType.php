<?php

namespace App\Enums;

enum IdentifierType: string {
    case Tag = "Tag";
    case Email = "Email";
    case Phone = "Phone";
    case AccountNumber = "AccountNumber";
    case Unknown = "Unknown";
}



