<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NextOfKin extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_setting_id',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'verification_type',
        'verification_doc_url',
        'relationship',
    ];

    /**
     * Get the account setting that owns the next of kin.
     */
    public function accountSetting()
    {
        return $this->belongsTo(AccountSetting::class);
    }
}