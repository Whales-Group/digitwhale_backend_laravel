<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'from_sys_account_id',
        'from_account',
        'from_user_name',
        'from_user_email',
        'currency',
        'to_sys_account_id',
        'to_user_name',
        'to_user_email',
        'to_bank_name',
        'to_bank_code',
        'to_account_number',
        'transaction_reference',
        'status',
        'type',
        'amount',
        'timestamp',
        'description',
        'entry_type',
        'charge',
        'source_amount',
        'amount_received',
        'from_bank',
        'source_currency',
        'destination_currency',
        'previous_balance',
        'new_balance',
    ];

    public static $promptProtect = [
        'from_sys_account_id',
        'to_sys_account_id',
        "id"
    ];
    protected $casts = [
        'timestamp' => 'datetime',
        'amount' => 'double',
    ];

    /**
     * Get the from system account.
     */
    public function fromSysAccount()
    {
        return $this->belongsTo(Account::class, 'from_sys_account_id');
    }

    /**
     * Get the to system account.
     */
    public function toSysAccount()
    {
        return $this->belongsTo(Account::class, 'to_sys_account_id');
    }
}