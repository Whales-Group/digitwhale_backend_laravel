<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        "first_name",
        "last_name",
        "email",
        "tag",
        "password",
        "email_verified_at",
    ];

    protected $hidden = [
        "password",
        "remember_token",
        "email_verified_at",
        "updated_at",
    ];

    protected $casts = [
        "email_verified_at" => "datetime",
        "password" => "hashed",
    ];

    public function fullName(): Attribute
    {
        return new Attribute(
            get: fn() => "{$this->first_name} {$this->last_name}"
        );
    }

    public function getFullName(): string
    {
        return $this->first_name . " " . $this->last_name;
    }

    public function account()
    {
        return $this->hasMany(Account::class);
    }

    protected static function booted()
    {
        static::created(function ($user) {

            Account::create([
                "user_id" => $user->id,
                "account_number" => self::generateUniqueBankAccountNumber(),
                "account_name" => $user->fullName,
                "balance" => 0.0,
                "type" => "tire1",
            ]);
        });
    }

    /**
     * Generate a unique 11-digit bank account number (TEST).
     *
     * @return string The generated unique 11-digit bank account number (TEST).
     */
    static function generateUniqueBankAccountNumber(): string
    {
        do {
            $randomNumber = mt_rand(0, 9999999999);
            $accountNumber = str_pad($randomNumber, 11, "0", STR_PAD_LEFT);

            $exists = Account::where(
                "account_number",
                $accountNumber
            )->exists();
        } while ($exists);

        return $accountNumber;
    }
}
