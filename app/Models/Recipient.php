<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipient extends Model
{
    use HasFactory;

    protected $fillable = [
        "active",
        "createdAt",
        "currency",
        "domain",
        "integration",
        "name",
        "recipient_code",
        "type",
        "updatedAt",
        "is_deleted",
        "account_number",
        "account_name",
        "bank_code",
        "bank_name",
    ];

    protected $casts = [
        "active" => "boolean",
        "createdAt" => "datetime",
        "updatedAt" => "datetime",
        "is_deleted" => "boolean",
    ];
}
