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
        'pnc',
        'blacklist_text',
        'dedicated_account_id',
        'account_number',
        'customer_id',
        'customer_code',
        'service_provider',
        'service_bank'
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

     /**
     * Update the account details.
     *
     * @param array $data The data to update.
     * @return bool Indicates if the update was successful.
     */
    public function updateAccount(array $data): bool
    {
        // Validate and sanitize input data
        $fillableFields = array_intersect_key($data, array_flip($this->fillable));

        // Ensure only updatable fields are updated
        return $this->fill($fillableFields)->save();
    }
}