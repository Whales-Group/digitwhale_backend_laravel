<?php

namespace App\Models;

use App\Models\Account;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        "first_name",
        "last_name",
        "middle_name",
        "business_name",
        "email",
        "tag",
        "password",
        "email_verified_at",
        "date_of_birth",
        "profile_url",
        "other_url",
        "phone_number",
        "profile_type",
        "gender",
        "nin",
        "bvn",
        "marital_status",
        "employment_status",
        "annual_income",
    ];

    protected $hidden = [
        "password",
        "remember_token",
    ];

    protected $casts = [
        "email_verified_at" => "datetime",
        "date_of_birth" => "date",
    ];

    public function fullName(): string
    {
        return "{$this->first_name} {$this->middle_name} {$this->last_name}";
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    protected static function booted()
    {
        static::created(function () {
            // 
        });
    }

    public function profileIsCompleted(): bool
    {
        $profileFields = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'phone_number' => $this->phone_number,
            'gender' => $this->gender,
            'bvn' => $this->bvn,
        ];

        return array_reduce($profileFields, fn($carry, $item) => $carry && !is_null($item), true);
    }
}