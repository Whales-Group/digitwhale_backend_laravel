<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaystsckDVA extends Model
{
    use HasFactory;

    protected $fillable = [
        "id",
        "bank_name",
        "bank_id",
        "bank_slug",
        "account_name",
        "account_number",
        "assigned",
        "currency",
        "active",
        "dva_id",
        "integration",
        "assignee_id",
        "assignee_type",
        "expired",
        "account_type",
        "assigned_at",
        "customer_id",
        "customer_first_name",
        "customer_last_name",
        "customer_email",
        "customer_code",
        "customer_phone",
        "customer_risk_action",
    ];
}
