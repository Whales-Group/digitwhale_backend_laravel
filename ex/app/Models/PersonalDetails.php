<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_setting_id',
        'first_name',
        'last_name',
        'middle_name',
        'tag',
        'date_of_birth',
        'gender',
        'phone_number',
        'email',
        'nin',
        'bvn',
        'marital_status',
        'employment_status',
        'annual_income',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Get the account setting that owns the personal details.
     */
    public function accountSetting()
    {
        return $this->belongsTo(AccountSetting::class);
    }
}