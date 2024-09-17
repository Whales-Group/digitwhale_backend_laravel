<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaystackCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        "email",
        "integration",
        "domain",
        "customer_code",
        "paystack_id",
        "identified",
        "identifications",
    ];

    protected $casts = [
        "identifications" => "array",
    ];
}
