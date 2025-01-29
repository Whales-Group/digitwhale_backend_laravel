<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_setting_id',
        'question',
        'answer',
    ];

    /**
     * Get the account setting that owns the security question.
     */
    public function accountSetting()
    {
        return $this->belongsTo(AccountSetting::class);
    }
}