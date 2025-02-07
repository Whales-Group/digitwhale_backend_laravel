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
        'address',
        'transaction_pin',
        'enabled_2fa',
        'fcm_tokens',
    ];

    protected $casts = [
        'hide_balance' => 'boolean',
        'enable_biometrics' => 'boolean',
        'enable_air_transfer' => 'boolean',
        'enable_notifications' => 'boolean',
        'enabled_2fa' => 'boolean',
        'fcm_tokens' => 'array',
    ];

    /**
     * Get the user that owns the account setting.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the verifications for the account setting.
     */
    public function verifications()
    {
        return $this->hasMany(VerificationRecord::class);
    }

    /**
     * Get the next of kin for the account setting.
     */
    public function nextOfKin()
    {
        return $this->hasOne(NextOfKin::class);
    }


    /**
     * Get the security questions for the account setting.
     */
    public function securityQuestions()
    {
        return $this->hasMany(SecurityQuestion::class);
    }
}