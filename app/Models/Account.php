<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'phone_number',
        'tag',
        'account_id',
        'balance',
        'account_type',
        'currency',
        'validated_name',
        'blacklisted',
        'enabled',
        'intrest_rate',
        'max_balance',
        'daily_transaction_limit',
        'daily_transaction_count',
        'pnd',
        'dedicated_account_id',
        'account_number',
        'customer_id',
        'customer_code',
        'service_provider',
    ];

    protected $casts = [
        'blacklisted' => 'boolean',
        'enabled' => 'boolean',
        'pnd' => 'boolean',
        'intrest_rate' => 'integer',
        'dedicated_account_id' => 'integer',
    ];

    /**
     * Get the user that owns the account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for the account.
     */
    public function transactions()
    {
        return $this->hasMany(TransactionEntry::class);
    }
}