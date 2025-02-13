<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hide_balance',
        'enable_biometrics',
        'enable_air_transfer',
        'enable_notifications',
        'transaction_pin',
        'enabled_2fa',
    ];

    protected $casts = [
        'hide_balance' => 'boolean',
        'enable_biometrics' => 'boolean',
        'enable_air_transfer' => 'boolean',
        'enable_notifications' => 'boolean',
        'enabled_2fa' => 'boolean',
    ];

    /**
     * Get the user that owns the account setting.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}