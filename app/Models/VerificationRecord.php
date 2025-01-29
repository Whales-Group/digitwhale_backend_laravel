<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_setting_id',
        'type',
        'status',
        'value',
        'url',
    ];

    /**
     * Get the account setting that owns the verification record.
     */
    public function accountSetting()
    {
        return $this->belongsTo(AccountSetting::class);
    }
}